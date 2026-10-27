# Sistema de Bodega FCF UdeC

Sistema web para la bodega de la Facultad de Ciencias Forestales de la Universidad de Concepción.
Controla dos áreas:

- **Insumos generales** (consumo): entradas, salidas, stock y aviso de stock mínimo.
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

## Desarrollo local

Requiere Docker. Desde la carpeta del proyecto:

```bash
cp config/config.example.php config/config.php
docker compose up -d --build
docker compose exec -u www-data app php bin/migrar.php
docker compose exec -u www-data app php bin/sembrar.php
```

- Sistema en http://localhost:8080 y phpMyAdmin en http://localhost:8081. Para cambiar puertos o
  credenciales, copiar `.env.example` como `.env`.
- `sembrar.php` carga el catálogo inicial y, solo en desarrollo local, tres cuentas de prueba:
  `admin@bodega.test`, `administrativa@bodega.test` y `docente@bodega.test`, con la clave `Bodega2026`.

Cuentas de usuario por consola:

```bash
docker compose exec -u www-data app php bin/crear-usuario.php
docker compose exec -u www-data app php bin/restablecer-clave.php --email=nombre@udec.cl
```

Con `--sql`, ninguno de los dos toca la base: imprimen la sentencia para ejecutarla en phpMyAdmin,
útil en un servidor sin acceso a consola.

## Pruebas

Con PHPUnit 12 como un solo archivo (`tools/phpunit.phar`, fuera de git: solo se usa en desarrollo, nunca
en el servidor). Las pruebas usan su propia base, `bodega_fcf_pruebas`, que se arma desde cero con las
migraciones en cada corrida; la base de desarrollo no se toca. Preparación, una sola vez:

```bash
curl -L https://phar.phpunit.de/phpunit-12.phar -o tools/phpunit.phar
docker compose exec db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" -e "CREATE DATABASE IF NOT EXISTS bodega_fcf_pruebas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL ON bodega_fcf_pruebas.* TO \"bodega\"@\"%\";"'
```

Correrlas (`--testdox` muestra el nombre de cada prueba en español):

```bash
docker compose exec -T app php tools/phpunit.phar --testdox
```

## Estructura

```
public/      Única carpeta expuesta a la web: index.php y assets (CSS, JS, fuentes, íconos, sonidos)
app/
  Core/      Núcleo propio: router, petición/respuesta, vistas, base de datos, sesión, CSRF, permisos
  Support/   Utilidades sin estado: fechas, RUT, números, íconos, tareas del inicio
  Modules/   Un módulo por área funcional (Insumos, Historial, Panel, Sectores, ...)
  views/     Layouts y fragmentos compartidos
config/      config.example.php (plantilla). config.php real fuera de git
database/    migrations/ (SQL numerado) y seeds/ (datos iniciales)
bin/         Scripts de consola: migrar, sembrar, crear usuario, restablecer clave
storage/     Logs y respaldos (escribible por el servidor, fuera de git)
tests/       Pruebas con PHPUnit (phpunit.xml.dist): Auth/ (inicio de sesión) y Core/
```

## Autor

David Campos, práctica profesional, Facultad de Ciencias Forestales, UdeC (2026).
