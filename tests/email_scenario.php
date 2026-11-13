<?php
declare(strict_types=1);
// Este processo usa configuracao temporaria e modelos em memoria, nunca o banco real.
namespace App\Models {
    final class Usuario {
        public static int $criados = 0;
        public static int $renovados = 0;
        public static function buscarPorEmail(string $email): ?array {
            if (($GLOBALS['modo'] ?? '') === 'cadastro' || $email !== 'destino@example.test') return null;
            return self::buscarPorId(123);
        }
        public static function gerarToken(): string { return str_repeat('a', 64); }
        public static function criar(string $nome, string $email, string $senha, string $token): int { ++self::$criados; return 123; }
        public static function buscarPorId(int $id): ?array { return ['id'=>123, 'nome'=>'Pessoa Teste', 'email'=>'destino@example.test', 'email_confirmado'=>(in_array($GLOBALS['modo'] ?? '', ['login_confirmado', 'guard_confirmado'], true) ? 1 : 0), 'senha_hash'=>password_hash('teste-seguro', PASSWORD_DEFAULT)]; }
        public static function renovarToken(int $id): string { ++self::$renovados; return str_repeat('b', 64); }
    }
}
namespace App\Core {
    final class Request {
        public function corpoJson(): array { return ['nome'=>'Pessoa Teste', 'email'=>(str_contains($GLOBALS['modo'] ?? '', 'desconhecido') ? 'desconhecido@example.test' : 'destino@example.test'), 'senha'=>(str_contains($GLOBALS['modo'] ?? '', 'invalido') ? 'senha-errada' : 'teste-seguro')]; }
    }
}
namespace {
    define('BASE_PATH', getenv('EMAIL_TEST_BASE'));
    require dirname(__DIR__) . '/src/autoload.php';
    \App\Core\Config::carregar(getenv('EMAIL_TEST_CONFIG'));
    require dirname(__DIR__) . '/src/funcoes.php';
    $_SERVER['HTTP_HOST'] = 'localhost';
    $modo = $argv[1] ?? 'email';
    ob_start();
    try {
        if (str_starts_with($modo, 'guard')) {
            $_SESSION = ['usuario_id'=>123, 'usuario_nome'=>'Pessoa Teste'];
            if ($modo !== 'guard_sem_flag') $_SESSION['usuario_confirmado'] = in_array($modo, ['guard_confirmado', 'guard_flag_incorreta'], true);
            $id = usuarioLogado();
            echo json_encode(['ok'=>$id !== null, 'id'=>$id]);
        } elseif ($modo === 'cadastro') {
            (new \App\Controllers\AuthController())->cadastrar(new \App\Core\Request(), []);
        } elseif (str_starts_with($modo, 'login')) {
            (new \App\Controllers\AuthController())->entrar(new \App\Core\Request(), []);
        } elseif (str_starts_with($modo, 'reenviar')) {
            (new \App\Controllers\ConfirmacaoController())->reenviar(new \App\Core\Request(), []);
        } else {
            \App\Core\Email::enviar('destino@example.test', 'Confirmação de e-mail', "Olá, Pessoa!\nhttp://localhost/confirmar/token");
        }
        $saida = ob_get_clean();
        echo json_encode(['exception'=>null, 'status'=>http_response_code() ?: 200, 'response'=>$saida === '' ? null : json_decode($saida, true), 'criados'=>\App\Models\Usuario::$criados, 'renovados'=>\App\Models\Usuario::$renovados, 'sessao'=>$_SESSION ?? []]);
    } catch (\Throwable $e) {
        ob_end_clean();
        echo json_encode(['exception'=>$e->getMessage(), 'type'=>get_class($e)]);
    }
}
