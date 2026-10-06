# Sistema de Bodega FCF UdeC

Sistema web para la bodega de la Facultad de Ciencias Forestales de la Universidad de Concepción.
Controla dos áreas:

- **Insumos generales** (consumo): entradas, salidas, stock, stock mínimo y pedido de materiales.
- **Herramientas e instrumentos de terreno** (préstamo): solicitudes, préstamos con fecha pactada,
  devoluciones, atrasos y daños.

Funciona como inventario perpetuo con código de barras: cada ítem se escanea al entrar o salir y el
stock se actualiza al confirmar la operación. El lector de códigos es opcional; todo se puede hacer
escribiendo el código o buscando por nombre.

> Estado: en desarrollo. Los requisitos son preliminares y pueden cambiar.

## Stack

| Capa          | Tecnología                                  |
|---------------|---------------------------------------------|
| Servidor      | PHP 8.5 puro (sin frameworks, sin Composer) |
| Base de datos | MySQL, InnoDB, utf8mb4                      |
| Cliente       | JavaScript vanilla y CSS puro               |
| Desarrollo    | Docker (PHP 8.5 + Apache + MySQL 8.4)       |

Sin librerías externas, CDNs ni servicios de terceros en producción: el servidor institucional
solo admite PHP, MySQL y JavaScript sin frameworks.

## Estructura

```
public/      Única carpeta expuesta a la web: index.php y assets (CSS, JS, fuentes, íconos, sonidos)
app/
  Core/      Núcleo propio: router, petición/respuesta, vistas, base de datos, sesión, CSRF, permisos
  Support/   Utilidades sin estado: fechas, RUT, códigos de barras
  Modules/   Un módulo por área funcional (Insumos, Herramientas, Estacion, ...)
  views/     Layouts y fragmentos compartidos
config/      config.example.php (plantilla). config.php real fuera de git
database/    migrations/ (SQL numerado) y seeds/ (datos iniciales)
bin/         Scripts de consola: migrar, crear administrador, respaldo
storage/     Logs y respaldos (escribible por el servidor, fuera de git)
tests/       Pruebas
docs/        Documentación técnica
```

## Autor

David Campos, práctica profesional, Facultad de Ciencias Forestales, UdeC (2026).
