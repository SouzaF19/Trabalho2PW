"""Testes isolados: python -m unittest discover -s tests -p test_email.py -v
Requer PHP (PHP_BINARY ou C:/xampp/php/php.exe). Nenhum banco ou envio externo.
"""
import json
import os
from pathlib import Path
import subprocess
import tempfile
import unittest

ROOT = Path(__file__).resolve().parents[1]
PHP = os.environ.get('PHP_BINARY', 'C:/xampp/php/php.exe')


class EmailTests(unittest.TestCase):
    def setUp(self):
        self.tmp = tempfile.TemporaryDirectory(prefix='anotaaqui-email-', dir=os.environ.get('TMPDIR'))
        self.addCleanup(self.tmp.cleanup)
        self.base = Path(self.tmp.name)
        self.cfg = {'debug': False, 'email': {'modo': 'smtp', 'remetente': '', 'nomeRemetente': 'AnotaAqui', 'horasValidade': 24, 'smtp': {'host': 'smtp.gmail.com', 'porta': 587, 'usuario': '', 'senha': '', 'seguranca': 'tls'}}}

    def run_php(self, mode='email', flags=()):
        # JSON e PHP pertencem ao mesmo subconjunto de literais neste fixture.
        config = self.base / 'config.php'
        config.write_text('<?php return json_decode(' + repr(json.dumps(self.cfg)) + ', true);', encoding='utf-8')
        env = dict(os.environ, EMAIL_TEST_BASE=str(self.base), EMAIL_TEST_CONFIG=str(config))
        result = subprocess.run([PHP, *flags, str(ROOT / 'tests/email_scenario.php'), mode], env=env, capture_output=True, text=True, encoding='utf-8', timeout=20)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(result.stderr, '', result.stderr)
        return json.loads(result.stdout)

    def smtp(self, **kwargs):
        from smtp_fixture import SmtpFixture
        server = SmtpFixture(self.base, **kwargs)
        self.addCleanup(server.close)
        self.cfg['email'].update(remetente='origem@example.test')
        self.cfg['email']['smtp'].update(host='localhost', porta=server.port, usuario='origem@example.test', senha='senha-ficticia', seguranca='tls')
        return server

    def test_smtp_starttls_autentica_e_envia_utf8(self):
        from email import policy
        from email.parser import BytesParser
        server = self.smtp()
        result = self.run_php(flags=('-d', 'openssl.cafile="' + server.ca.as_posix() + '"'))
        server.thread.join(2)
        self.assertIsNone(result['exception'])
        self.assertEqual(len(server.messages), 1)
        self.assertIn('STARTTLS', server.commands)
        self.assertIn('AUTH', server.commands)
        message = BytesParser(policy=policy.default).parsebytes(server.messages[0])
        self.assertEqual(message['Subject'], 'Confirmação de e-mail')
        self.assertEqual(message['From'].addresses[0].addr_spec, 'origem@example.test')
        self.assertEqual(message['To'].addresses[0].addr_spec, 'destino@example.test')
        self.assertIn('Olá, Pessoa!', message.get_content())

    def test_configuracao_smtp_invalida_falha_sem_rede(self):
        self.cfg['email'].update(remetente='origem@example.test')
        self.cfg['email']['smtp'].update(usuario='origem@example.test', senha='senha-ficticia')
        for field, value in [('seguranca', ''), ('seguranca', 'inseguro'), ('porta', 0), ('porta', 65536), ('host', '')]:
            with self.subTest(field=field, value=value):
                original = self.cfg['email']['smtp'][field]
                self.cfg['email']['smtp'][field] = value
                result = self.run_php()
                self.assertIn(field, result['exception'] or '')
                self.cfg['email']['smtp'][field] = original

    def test_cadastro_falha_email_conta_existe_login_e_reenvio_possiveis(self):
        result = self.run_php('cadastro')
        self.assertIsNone(result['exception'])
        self.assertEqual(result['status'], 503)
        self.assertEqual(result['criados'], 1)
        self.assertNotIn('usuario_id', result['sessao'])
        self.assertFalse(result['response']['ok'])
        self.assertTrue(result['response']['conta_criada'])
        self.assertFalse(result['response']['email_enviado'])
        self.assertIn('Entrar', result['response']['erro'])
        self.assertNotIn('config.php', result['response']['erro'])

    def test_reenvio_falha_resposta_honesta_permite_nova_tentativa(self):
        result = self.run_php('reenviar')
        self.assertIsNone(result['exception'])
        self.assertEqual(result['status'], 503)
        self.assertFalse(result['response']['ok'])
        self.assertIn('tente novamente', result['response']['erro'])
        self.assertEqual(result['criados'], 0)
        self.assertEqual(result['renovados'], 1)

    def test_smtp_rejeita_certificado_nao_confiavel(self):
        server = self.smtp()
        result = self.run_php()
        self.assertIsNotNone(result['exception'])
        self.assertEqual(server.messages, [])
        self.assertNotIn('AUTH', server.commands)

    def test_smtp_rejeicao_auth_e_data_nao_finge_sucesso(self):
        for rejection in ('reject_auth', 'reject_data'):
            with self.subTest(rejection=rejection):
                server = self.smtp(**{rejection: True})
                result = self.run_php(flags=('-d', 'openssl.cafile="' + server.ca.as_posix() + '"'))
                self.assertIsNotNone(result['exception'])
                self.assertNotIn('senha-ficticia', result['exception'])
                self.assertEqual(server.messages, [])
                server.close()

    def test_cadastro_e_reenvio_smtp_sucesso_sem_link_de_demo(self):
        for mode in ('cadastro', 'reenviar'):
            with self.subTest(mode=mode):
                server = self.smtp()
                result = self.run_php(mode, flags=('-d', 'openssl.cafile="' + server.ca.as_posix() + '"'))
                self.assertIsNone(result['exception'])
                self.assertEqual(result['status'], 201 if mode == 'cadastro' else 200)
                self.assertTrue(result['response']['ok'])
                self.assertNotIn('usuario_id', result['sessao'])
                self.assertNotIn('link_confirmacao', result['response'])
                self.assertEqual(len(server.messages), 1)
                self.assertIn(b'/confirmar/', server.messages[0])
                server.close()

    def test_log_opcional_nao_envia_e_demo_so_com_debug(self):
        self.cfg['email']['modo'] = 'log'
        self.cfg['email']['remetente'] = 'origem@example.test'
        for debug in (False, True):
            self.cfg['debug'] = debug
            result = self.run_php('reenviar')
            self.assertTrue(result['response']['ok'])
            self.assertEqual('link_confirmacao' in result['response'], debug)
        self.assertIn('/confirmar/', (self.base / 'storage/emails.log').read_text(encoding='utf-8'))

    def test_usuario_e_senha_ausentes_tem_diagnostico_claro(self):
        self.cfg['email']['remetente'] = 'origem@example.test'
        result = self.run_php()
        self.assertIn('usuario', result['exception'])
        self.cfg['email']['smtp']['usuario'] = 'origem@example.test'
        result = self.run_php()
        self.assertIn('senha', result['exception'])

    def test_configuracoes_padrao_gmail_smtp_sem_segredos(self):
        for filename in ('config.example.php',):
            script = "$c = require $argv[1]; $e = $c['email']; echo json_encode(($e['modo'] ?? '') === 'smtp' && ($e['remetente'] ?? null) === '' && ($e['smtp']['host'] ?? '') === 'smtp.gmail.com' && ($e['smtp']['porta'] ?? 0) === 587 && ($e['smtp']['seguranca'] ?? '') === 'tls' && ($e['smtp']['usuario'] ?? null) === '' && ($e['smtp']['senha'] ?? null) === '');"
            result = subprocess.run([PHP, '-r', script, str(ROOT / 'config' / filename)], capture_output=True, text=True, timeout=10)
            self.assertEqual(result.returncode, 0, result.stderr)
            self.assertEqual(result.stdout, 'true', filename)

    def test_login_nao_confirmado_bloqueado_sem_sessao(self):
        result = self.run_php('login')
        self.assertIsNone(result['exception'])
        self.assertEqual(result['status'], 403)
        self.assertEqual(result['response']['codigo'], 'email_nao_confirmado')
        self.assertNotIn('usuario_id', result['sessao'])

    def test_reenvio_pre_login_rejeita_senha_invalida_sem_renovar(self):
        result = self.run_php('reenviar_invalido')
        self.assertIsNone(result['exception'])
        self.assertEqual(result['status'], 401)
        self.assertEqual(result['renovados'], 0)
        self.assertNotIn('usuario_id', result['sessao'])

    def test_login_senha_invalida_sem_sessao(self):
        result = self.run_php('login_invalido')
        self.assertEqual(result['status'], 401)
        self.assertNotIn('usuario_id', result['sessao'])

    def test_guard_rejeita_sessao_antiga_nao_confirmada(self):
        for mode in ('guard_nao_confirmado', 'guard_sem_flag', 'guard_flag_incorreta'):
            result = self.run_php(mode)
            self.assertIsNone(result['exception'])
            self.assertFalse(result['response']['ok'])
            self.assertNotIn('usuario_id', result['sessao'])

    def test_guard_aceita_confirmado(self):
        result = self.run_php('guard_confirmado')
        self.assertTrue(result['response']['ok'])
        self.assertEqual(result['response']['id'], 123)

    def test_email_desconhecido_nao_renova_e_nao_revela_conta(self):
        for mode in ('reenviar_desconhecido', 'login_desconhecido'):
            result = self.run_php(mode)
            self.assertIsNone(result['exception'])
            self.assertEqual(result['status'], 401)
            self.assertEqual(result['response']['erro'], 'E-mail ou senha incorretos.')
            self.assertEqual(result['renovados'], 0)
            self.assertNotIn('usuario_id', result['sessao'])

    def test_login_confirmado_permitido(self):
        result = self.run_php('login_confirmado')
        self.assertEqual(result['status'], 200)
        self.assertEqual(result['sessao'].get('usuario_id'), 123)

    def test_credenciais_ausentes_falham_antes_da_rede(self):
        result = self.run_php()
        self.assertIn('remetente', result['exception'])
        self.assertIn('config/config.php', result['exception'])


if __name__ == '__main__':
    unittest.main()
