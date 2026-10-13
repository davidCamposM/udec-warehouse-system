<?php

declare(strict_types=1);

namespace App\Modules\Personas;

use App\Core\Database;
use App\Core\ValidationException;
use App\Modules\Historial\AuditoriaService;
use App\Support\Rut;

defined('APP_ROOT') || exit;

/**
 * Personas que retiran en el punto de retiro sin cuenta en el sistema: se identifican con su credencial (PER00001)
 * o su RUT. Solo se guarda lo necesario para identificarlas (Ley 19.628).
 */
final class PersonaService
{
    /** tipo => [nombre, qué puede hacer] */
    public const TIPOS = [
        'auxiliar' => ['Auxiliar de bodega', 'Ayuda en la bodega y retira lo que se necesita'],
        'autorizada' => ['Persona autorizada', 'Retira insumos para su trabajo'],
        'docente' => ['Docente', 'Retira para sus clases o salidas a terreno'],
    ];

    public function __construct(
        private readonly PersonaRepository $personas = new PersonaRepository(),
        private readonly AuditoriaService $auditoria = new AuditoriaService(),
    ) {
    }

    /** @return list<array<string, mixed>> */
    public function todas(): array
    {
        return $this->personas->todas();
    }

    /** @return array<string, mixed>|null */
    public function buscarPorId(int $id): ?array
    {
        return $this->personas->findById($id);
    }

    /**
     * La persona de una credencial leída o de un RUT escrito (con o sin puntos y guion), esté activa o no.
     *
     * @return array<string, mixed>|null
     */
    public function identificar(string $texto): ?array
    {
        $texto = trim($texto);

        return $texto === '' ? null : $this->personas->findPorCredencialORut(strtoupper($texto), Rut::normalizar($texto));
    }

    /**
     * @param array<string, mixed> $datos
     * @return array{id: int, codigo: string, nombre: string}
     * @throws ValidationException
     */
    public function crear(array $datos): array
    {
        $persona = $this->validar($datos, null);

        return Database::transaction(function () use ($persona): array {
            $codigo = $this->personas->siguienteCodigo();
            $id = $this->personas->create($codigo, $persona);
            $this->auditoria->registrar('crear', 'persona', $id, ['codigo' => $codigo, 'tipo' => $persona['tipo']]);

            return ['id' => $id, 'codigo' => $codigo, 'nombre' => $persona['nombre']];
        });
    }

    /**
     * @param array<string, mixed> $datos
     * @throws ValidationException
     */
    public function editar(int $id, array $datos): void
    {
        $actual = $this->personas->findById($id) ?? throw new ValidationException(['nombre' => 'Esa persona no existe.']);
        $nueva = $this->validar($datos, $id);

        $cambios = AuditoriaService::diferencias($actual, $nueva);
        if ($cambios === []) {
            return;
        }
        // El historial dice que el RUT cambió, sin guardar el RUT mismo.
        if (isset($cambios['rut'])) {
            $cambios['rut'] = ['(oculto)', '(oculto)'];
        }

        Database::transaction(function () use ($id, $nueva, $cambios): void {
            $this->personas->update($id, $nueva);
            $this->auditoria->registrar('editar', 'persona', $id, ['cambios' => $cambios]);
        });
    }

    public function cambiarActivo(int $id, bool $activo): void
    {
        $persona = $this->personas->findById($id);

        if ($persona === null || (int) $persona['activo'] === (int) $activo) {
            return;
        }

        Database::transaction(function () use ($id, $activo, $persona): void {
            $this->personas->setActivo($id, $activo);
            $this->auditoria->registrar($activo ? 'activar' : 'desactivar', 'persona', $id, ['codigo' => $persona['codigo_credencial']]);
        });
    }

    /**
     * @param array<string, mixed> $datos
     * @return array{nombre: string, rut: ?string, tipo: string, departamento: ?string}
     * @throws ValidationException
     */
    private function validar(array $datos, ?int $id): array
    {
        $texto = static fn (string $campo): string => is_string($datos[$campo] ?? null)
            ? (string) preg_replace('/\s+/u', ' ', trim($datos[$campo]))
            : '';

        $nombre = $texto('nombre');
        $tipo = $texto('tipo');
        $rut = $texto('rut');
        $departamento = $texto('departamento');
        $errores = [];

        if ($nombre === '') {
            $errores['nombre'] = 'Escribe el nombre completo. Ejemplo: Juan Pérez Soto.';
        } elseif (mb_strlen($nombre) > 120) {
            $errores['nombre'] = 'El nombre es muy largo: usa 120 letras como máximo.';
        }

        if (!isset(self::TIPOS[$tipo])) {
            $errores['tipo'] = 'Elige qué es esta persona.';
        }

        if ($rut !== '') {
            if (!Rut::esValido($rut)) {
                $errores['rut'] = 'El RUT no es válido. Revisa los números y el dígito final. Ejemplo: 12.345.678-5.';
            } else {
                $rut = (string) Rut::normalizar($rut);
                $otra = $this->personas->nombrePorRut($rut, $id);
                if ($otra !== null) {
                    $errores['rut'] = "Ese RUT ya está registrado para {$otra}.";
                }
            }
        }

        if (mb_strlen($departamento) > 120) {
            $errores['departamento'] = 'Es muy largo: usa 120 letras como máximo.';
        }

        if ($errores !== []) {
            throw new ValidationException($errores);
        }

        return [
            'nombre' => $nombre,
            'rut' => $rut === '' ? null : $rut,
            'tipo' => $tipo,
            'departamento' => $departamento === '' ? null : $departamento,
        ];
    }
}
