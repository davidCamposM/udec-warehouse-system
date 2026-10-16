<?php

declare(strict_types=1);

namespace App\Support;

use App\Core\Config;
use App\Core\Logger;
use RuntimeException;
use Throwable;

defined('APP_ROOT') || exit;

/**
 * Envío de correos de texto (los enlaces para crear o recuperar la contraseña), sin librerías. Según correo.modo:
 *   'archivo' (desarrollo): no envía; deja cada correo en storage/logs/correos-aaaa-mm-dd.log.
 *   'smtp': los envía con la casilla de config (STARTTLS en 587, o SSL en 465, con usuario y clave).
 * Varios correos se envían por una sola conexión (enviarVarios), como al cargar una lista de personas.
 */
final class Correo
{
    /** @var resource|null */
    private $conexion = null;

    /** Si el sistema envía correos de verdad (tiene casilla configurada: correo.modo = smtp). */
    public static function envia(): bool
    {
        return Config::get('correo.modo', 'archivo') === 'smtp';
    }

    /** @return bool si se envió (o se guardó, en modo archivo) */
    public static function enviar(string $para, string $asunto, string $texto): bool
    {
        return self::enviarVarios([[$para, $asunto, $texto]]) === [];
    }

    /**
     * @param list<array{0: string, 1: string, 2: string}> $correos [para, asunto, texto]
     * @return list<string> los correos a los que no se pudo enviar
     */
    public static function enviarVarios(array $correos): array
    {
        if ($correos === []) {
            return [];
        }

        if (Config::get('correo.modo', 'archivo') !== 'smtp') {
            return self::guardarEnArchivo($correos);
        }

        $smtp = new self();
        $fallidos = [];
        try {
            $smtp->conectar();
        } catch (Throwable $e) {
            Logger::error('No se pudo conectar al servidor de correo: ' . $e->getMessage());

            return array_column($correos, 0);
        }

        foreach ($correos as $i => [$para, $asunto, $texto]) {
            try {
                $smtp->entregar($para, $asunto, $texto);
            } catch (Throwable $e) {
                Logger::error('No se pudo enviar un correo: ' . $e->getMessage(), ['para' => $para]);
                $fallidos[] = $para;
                // Tras un error, el servidor puede quedar a mitad de un envío: se empieza de nuevo. Si la conexión se
                // cortó, los que faltaban tampoco salen.
                try {
                    $smtp->comando('RSET', [250]);
                } catch (Throwable) {
                    array_push($fallidos, ...array_column(array_slice($correos, $i + 1), 0));
                    break;
                }
            }
        }
        $smtp->cerrar();

        return $fallidos;
    }

    /**
     * @param list<array{0: string, 1: string, 2: string}> $correos
     * @return list<string>
     */
    private static function guardarEnArchivo(array $correos): array
    {
        $archivo = APP_ROOT . '/storage/logs/correos-' . date('Y-m-d') . '.log';
        $fallidos = [];
        foreach ($correos as [$para, $asunto, $texto]) {
            $registro = sprintf("=== %s · Para: %s\nAsunto: %s\n\n%s\n\n", date('Y-m-d H:i:s'), $para, $asunto, $texto);
            if (@file_put_contents($archivo, $registro, FILE_APPEND | LOCK_EX) === false) {
                $fallidos[] = $para;
            }
        }

        return $fallidos;
    }

    private function conectar(): void
    {
        $host = (string) Config::get('correo.host', '');
        $puerto = (int) Config::get('correo.puerto', 587);
        $seguridad = (string) Config::get('correo.seguridad', 'tls');
        if ($host === '') {
            throw new RuntimeException('Falta correo.host en config/config.php.');
        }

        $contexto = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
        $conexion = @stream_socket_client(
            ($seguridad === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $puerto,
            $codigo,
            $mensaje,
            20,
            STREAM_CLIENT_CONNECT,
            $contexto,
        );
        if ($conexion === false) {
            throw new RuntimeException("{$host}:{$puerto} · {$mensaje} ({$codigo})");
        }
        stream_set_timeout($conexion, 20);
        $this->conexion = $conexion;

        $this->leer([220]);
        $nombre = (string) (parse_url((string) Config::get('app.base_url', ''), PHP_URL_HOST) ?: 'localhost');
        $this->comando('EHLO ' . $nombre, [250]);

        if ($seguridad === 'tls') {
            $this->comando('STARTTLS', [220]);
            if (stream_socket_enable_crypto($this->conexion, true, STREAM_CRYPTO_METHOD_TLS_CLIENT) !== true) {
                throw new RuntimeException('No se pudo cifrar la conexión (STARTTLS).');
            }
            $this->comando('EHLO ' . $nombre, [250]);
        }

        $usuario = (string) Config::get('correo.usuario', '');
        if ($usuario !== '') {
            $this->comando('AUTH LOGIN', [334]);
            $this->comando(base64_encode($usuario), [334]);
            $this->comando(base64_encode((string) Config::get('correo.clave', '')), [235]);
        }
    }

    private function entregar(string $para, string $asunto, string $texto): void
    {
        $remitente = (string) Config::get('correo.remitente', Config::get('correo.usuario', ''));
        $para = str_replace(["\r", "\n", '<', '>'], '', $para);

        $this->comando('MAIL FROM:<' . $remitente . '>', [250]);
        $this->comando('RCPT TO:<' . $para . '>', [250, 251]);
        $this->comando('DATA', [354]);

        $mensaje = implode("\r\n", [
            'Date: ' . date('r'),
            'From: ' . self::encabezado((string) Config::get('correo.nombre_remitente', 'Bodega FCF UdeC')) . ' <' . $remitente . '>',
            'To: <' . $para . '>',
            'Subject: ' . self::encabezado($asunto),
            'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . (substr(strrchr($remitente, '@') ?: '@localhost', 1)) . '>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            '',
            rtrim(chunk_split(base64_encode(str_replace("\n", "\r\n", str_replace("\r\n", "\n", $texto))), 76, "\r\n")),
        ]);

        // El cuerpo va en base64: ninguna línea empieza con un punto, así que no hace falta duplicarlos.
        fwrite($this->conexion, $mensaje . "\r\n.\r\n");
        $this->leer([250]);
    }

    private function cerrar(): void
    {
        if ($this->conexion !== null) {
            try {
                $this->comando('QUIT', [221]);
            } catch (Throwable) {
                // Ya se envió todo: si el cierre falla, da lo mismo.
            }
            fclose($this->conexion);
            $this->conexion = null;
        }
    }

    /** @param list<int> $esperados códigos de respuesta válidos */
    private function comando(string $linea, array $esperados): string
    {
        if ($this->conexion === null || fwrite($this->conexion, $linea . "\r\n") === false) {
            throw new RuntimeException('Se cortó la conexión con el servidor de correo.');
        }

        return $this->leer($esperados);
    }

    /**
     * La respuesta del servidor (puede ocupar varias líneas: «250-…» sigue, «250 …» termina).
     *
     * @param list<int> $esperados
     */
    private function leer(array $esperados): string
    {
        $respuesta = '';
        while (($linea = fgets($this->conexion, 1024)) !== false) {
            $respuesta .= $linea;
            if (strlen($linea) < 4 || $linea[3] === ' ') {
                break;
            }
        }

        $codigo = (int) substr($respuesta, 0, 3);
        if (!in_array($codigo, $esperados, true)) {
            throw new RuntimeException('El servidor de correo respondió: ' . trim($respuesta ?: 'nada (tiempo agotado)'));
        }

        return $respuesta;
    }

    /** Un texto con tildes en un encabezado (asunto, nombre): =?UTF-8?B?…?= */
    private static function encabezado(string $texto): string
    {
        $texto = str_replace(["\r", "\n"], ' ', $texto);

        return preg_match('/[^\x20-\x7E]/', $texto) === 1 ? '=?UTF-8?B?' . base64_encode($texto) . '?=' : $texto;
    }
}
