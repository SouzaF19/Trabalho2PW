const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const root = path.join(__dirname, '..');

function setup({ login = false, cadastro = false, legacy = false, search = '', response = { ok: true, data: {} } } = {}) {
    const handlers = {};
    const error = { textContent: '', hidden: true };
    const status = { textContent: '', hidden: true };
    const button = { disabled: false, addEventListener(type, fn) { handlers.resend = fn; } };
    const form = {
        values: { email: 'pessoa@example.com', senha: 'senha-secreta' },
        querySelector(selector) { return selector === '#erro' ? error : null; },
        reportValidity() { return true; },
        addEventListener(type, fn) { handlers.submit = fn; }
    };
    const calls = [];
    const elements = { 'form-login': login ? form : null, 'form-cadastro': cadastro ? form : null, 'btn-reenviar': (login || legacy) ? button : null, 'aviso-estado': status, 'aviso-cadastro': status };
    const context = vm.createContext({
        document: { getElementById(id) { return elements[id] || null; } },
        window: { location: { href: '', search } }, URLSearchParams,
        FormData: class { constructor(f) { return Object.entries(f.values); } },
        BASE: '/app', fetch: async (url, options) => { calls.push({ url, options }); return { ok: response.ok, status: response.status || 200, json: async () => response.data }; }
    });
    for (const file of ['app.js', 'auth.js']) vm.runInContext(fs.readFileSync(path.join(root, 'public/assets/js', file), 'utf8'), context);
    return { handlers, error, status, button, form, calls, context };
}

test('cadastro criado redireciona para login com confirmação', async () => {
    const s = setup({ cadastro: true, response: { ok: true, data: { ok: true, email_enviado: true } } });
    await s.handlers.submit({ preventDefault() {} });
    assert.equal(s.context.window.location.href, '/app/login?cadastro=confirmar');
});

test('cadastro criado sem envio redireciona com aviso de falha', async () => {
    const s = setup({ cadastro: true, response: { ok: false, status: 503, data: { ok: false, conta_criada: true, email_enviado: false } } });
    await s.handlers.submit({ preventDefault() {} });
    assert.equal(s.context.window.location.href, '/app/login?cadastro=envio_falhou');
});

test('erro comum de cadastro não redireciona', async () => {
    const s = setup({ cadastro: true, response: { ok: false, status: 400, data: { erro: 'Dados inválidos' } } });
    await s.handlers.submit({ preventDefault() {} });
    assert.equal(s.context.window.location.href, '');
    assert.equal(s.error.textContent, 'Dados inválidos');
});

test('login exibe avisos apenas para valores autorizados', () => {
    const success = setup({ login: true, search: '?cadastro=confirmar' });
    assert.match(success.status.textContent, /confir/i);
    const failed = setup({ login: true, search: '?cadastro=envio_falhou' });
    assert.match(failed.status.textContent, /não|falh/i);
    const other = setup({ login: true, search: '?cadastro=%3Cscript%3E' });
    assert.equal(other.status.textContent, '');
});

test('erro de login não confirmado orienta reenvio', async () => {
    const s = setup({ login: true, response: { ok: false, status: 403, data: { erro: 'Confirme seu e-mail', codigo: 'email_nao_confirmado' } } });
    await s.handlers.submit({ preventDefault() {} });
    assert.match(s.error.textContent, /reenvi/i);
});

test('reenvio usa os campos atuais e permite nova tentativa', async () => {
    const s = setup({ login: true, response: { ok: true, data: { ok: true } } });
    s.form.values = { email: 'novo@example.com', senha: 'outra-senha' };
    await s.handlers.resend();
    assert.equal(s.calls[0].url, '/app/api/reenviar-confirmacao');
    assert.deepEqual(JSON.parse(s.calls[0].options.body), s.form.values);
    assert.equal(s.button.disabled, false);
    assert.match(s.status.textContent, /envi/i);
});

test('reenvio inválido não chama API', async () => {
    const s = setup({ login: true });
    s.form.reportValidity = () => false;
    await s.handlers.resend();
    assert.equal(s.calls.length, 0);
});

test('reenvio com erro mostra mensagem e libera botão', async () => {
    const s = setup({ login: true, response: { ok: false, status: 401, data: { erro: 'Credenciais inválidas' } } });
    await s.handlers.resend();
    assert.equal(s.status.textContent, 'Credenciais inválidas');
    assert.equal(s.button.disabled, false);
});

test('chamarApi preserva dados de erro', async () => {
    const s = setup({ response: { ok: false, status: 403, data: { erro: 'Negado', codigo: 'email_nao_confirmado' } } });
    await assert.rejects(s.context.chamarApi('POST', '/api/login', {}), e => e.message === 'Negado' && e.dados.codigo === 'email_nao_confirmado');
});

test('login oferece reenvio acessível antes de autenticar', () => {
    const html = fs.readFileSync(path.join(root, 'views/login.php'), 'utf8');
    assert.match(html, /id="btn-reenviar"/);
    assert.match(html, /type="button"[^>]*id="btn-reenviar"|id="btn-reenviar"[^>]*type="button"/);
    assert.match(html, /id="aviso-cadastro"[^>]*role="status"|role="status"[^>]*id="aviso-cadastro"/);
    assert.match(html, /id="erro"[^>]*role="alert"|role="alert"[^>]*id="erro"/);
});


test('botão legado orienta a solicitar reenvio no login', async () => {
    const s = setup({ legacy: true });
    await s.handlers.resend();
    assert.match(s.status.textContent, /login/i);
    assert.equal(s.calls.length, 0);
});
