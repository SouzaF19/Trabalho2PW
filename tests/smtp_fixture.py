"""Servidor SMTP de teste, apenas loopback, com STARTTLS e autenticacao ficticia."""
import socket
import ssl
import threading
from datetime import datetime, timedelta, timezone
from pathlib import Path
from cryptography import x509
from cryptography.x509.oid import NameOID
from cryptography.hazmat.primitives import hashes, serialization
from cryptography.hazmat.primitives.asymmetric import rsa


class SmtpFixture:
    def __init__(self, directory, reject_auth=False, reject_data=False):
        self.messages = []
        self.commands = []
        self.error = None
        self.reject_auth = reject_auth
        self.reject_data = reject_data
        key = rsa.generate_private_key(public_exponent=65537, key_size=2048)
        name = x509.Name([x509.NameAttribute(NameOID.COMMON_NAME, 'localhost')])
        now = datetime.now(timezone.utc)
        cert = (x509.CertificateBuilder().subject_name(name).issuer_name(name)
                .public_key(key.public_key()).serial_number(x509.random_serial_number())
                .not_valid_before(now - timedelta(minutes=1)).not_valid_after(now + timedelta(days=1))
                .add_extension(x509.SubjectAlternativeName([x509.DNSName('localhost')]), critical=False)
                .add_extension(x509.BasicConstraints(ca=True, path_length=None), critical=True)
                .sign(key, hashes.SHA256()))
        self.ca = Path(directory) / 'smtp-test-ca.pem'
        self.ca.write_bytes(cert.public_bytes(serialization.Encoding.PEM))
        private = Path(directory) / 'smtp-test-key.pem'
        private.write_bytes(key.private_bytes(serialization.Encoding.PEM, serialization.PrivateFormat.PKCS8, serialization.NoEncryption()))
        self.context = ssl.SSLContext(ssl.PROTOCOL_TLS_SERVER)
        self.context.load_cert_chain(self.ca, private)
        self.listener = socket.socket()
        self.listener.bind(('127.0.0.1', 0))
        self.port = self.listener.getsockname()[1]
        self.listener.listen(1)
        self.listener.settimeout(10)
        self.thread = threading.Thread(target=self.serve, daemon=True)
        self.thread.start()

    def serve(self):
        try:
            conn, _ = self.listener.accept()
            conn.settimeout(10)
            with conn:
                stream = conn.makefile('rwb', buffering=0)
                def send(line):
                    stream.write((line + '\r\n').encode())
                send('220 localhost test SMTP')
                tls = False
                while True:
                    line = stream.readline().decode().strip()
                    if not line:
                        break
                    command = line.split()[0].upper()
                    self.commands.append(command)
                    if command == 'EHLO':
                        send('250-localhost')
                        send('250-AUTH LOGIN')
                        send('250 OK' if tls else '250 STARTTLS')
                    elif command == 'STARTTLS':
                        send('220 Ready')
                        stream.close()
                        conn = self.context.wrap_socket(conn, server_side=True)
                        stream = conn.makefile('rwb', buffering=0)
                        tls = True
                    elif command == 'AUTH':
                        send('334 VXNlcm5hbWU6')
                        stream.readline()
                        send('334 UGFzc3dvcmQ6')
                        stream.readline()
                        send('535 Authentication rejected' if self.reject_auth else '235 Authenticated')
                    elif command in ('MAIL', 'RCPT', 'RSET'):
                        send('250 OK')
                    elif command == 'DATA':
                        send('354 Send message')
                        lines = []
                        while True:
                            part = stream.readline()
                            if part in (b'.\r\n', b''):
                                break
                            lines.append(part)
                        if not self.reject_data:
                            self.messages.append(b''.join(lines))
                        send('554 Message rejected' if self.reject_data else '250 Queued')
                    elif command == 'QUIT':
                        send('221 Bye')
                        break
                    else:
                        send('502 Unsupported')
                stream.close()
                conn.close()
        except (ssl.SSLError, ConnectionResetError, BrokenPipeError, TimeoutError):
            pass  # Cliente deve rejeitar o certificado no teste negativo.
        except Exception as exc:
            self.error = repr(exc)
        finally:
            self.listener.close()

    def close(self):
        self.thread.join(12)
        self.listener.close()
        if self.thread.is_alive():
            raise AssertionError('SMTP fixture nao encerrou')
        if self.error:
            raise AssertionError(self.error)
