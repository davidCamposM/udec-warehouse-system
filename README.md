# Bodega FCF UdeC

Sistema web para la bodega de la Facultad de Ciencias Forestales de la Universidad de Concepción.

Permite llevar el control de:

- **Insumos:** ingresos, retiros, stock y productos por reponer.
- **Herramientas:** catálogo, solicitudes de los docentes, préstamos y devoluciones.

Funciona en computadores y celulares. Se puede usar con lector de código de barras o escribiendo el código o el
nombre del producto.

## Requisitos

- PHP 8.4 o superior, con las extensiones `pdo_mysql`, `mbstring`, `zip` e `intl`
- MySQL 8 o MariaDB
- Apache con `mod_rewrite` y `.htaccess` habilitados

No usa frameworks ni librerías externas.

## Instalación

1. Copiar el proyecto al servidor.
2. Crear una base de datos vacía en MySQL (`utf8mb4`).
3. Copiar `config/config.example.php` como `config/config.php` y completar los datos de la base de datos y la
   dirección del sistema.
4. Crear las tablas y cargar los datos iniciales:

   ```bash
   php bin/migrar.php
   php bin/sembrar.php
   ```

5. Crear la primera cuenta de administrador:

   ```bash
   php bin/crear-usuario.php
   ```

La carpeta `storage/` debe tener permiso de escritura para Apache.

## Actualizar

Después de descargar una versión nueva, aplicar los cambios de la base de datos:

```bash
php bin/migrar.php
```

## Cuentas

Para restablecer la contraseña de una persona:

```bash
php bin/restablecer-clave.php --email=nombre@udec.cl
```

Si el servidor no tiene acceso a consola, ambos scripts aceptan `--sql`: muestran la instrucción para ejecutarla
en phpMyAdmin.

## Carpetas

```
public/      Archivos visibles en la web (index.php, estilos, scripts e imágenes)
app/         Código del sistema
config/      Configuración
database/    Estructura de la base de datos y datos iniciales
bin/         Scripts de instalación y cuentas
storage/     Registros y archivos del sistema
```

## Autor

David Campos, Facultad de Ciencias Forestales, Universidad de Concepción, 2026.
