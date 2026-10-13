# GolApp - MVP (PHP + MySQL)

Sistema web de gestión de alquiler e inventario para canchas sintéticas del Complejo Deportivo Luis Amaranto Perea (Carepa, Urabá).
Proyecto de Ingeniería del Software 3 - Parcial II, Fase 2 (entregable 2.2).

**Tecnologías:** PHP 8 (PDO) · MySQL/MariaDB · HTML, CSS y JavaScript (sin frameworks).

## Instalación (XAMPP o MAMP en Mac)
1. Copia la carpeta del proyecto en `htdocs` con el nombre `golapp` (XAMPP: `/Applications/XAMPP/htdocs/golapp`; MAMP: `/Applications/MAMP/htdocs/golapp`).
2. Inicia Apache y MySQL.
3. Abre phpMyAdmin (`http://localhost/phpmyadmin`) > **Importar** > selecciona `database/golapp.sql` > Continuar.
4. Revisa `includes/config.php`: XAMPP usa usuario `root` sin contraseña; en MAMP cambia `DB_PASS` a `'root'` (y `DB_PORT` a `'8889'`).
5. Abre `http://localhost/golapp/` (en MAMP: `http://localhost:8888/golapp/`).

## Usuarios de prueba
| Rol | Correo | Contraseña |
|---|---|---|
| Administrador | admin@golapp.com | Admin123* |
| Cliente | cliente@golapp.com | Cliente123* |

Los clientes nuevos se registran desde la pantalla "Crear cuenta" (siempre con rol cliente).

## Funcionalidades (requisitos esenciales)
- **Registro y autenticación de dos roles** (cliente y administrador) con sesiones PHP y control de acceso por rol.
- **Canchas y tipos de cancha** (CRUD, precio por hora) - administrador.
- **Calendario de disponibilidad** por día (cancha x hora) y **reservas** sin cruces de horario (transacción con bloqueo de fila).
  El cliente reserva (queda *pendiente*); el administrador confirma, modifica, cancela o finaliza, y puede crear reservas manuales.
- **Inventario**: implementos con stock total, disponibilidad "en uso ahora" y asignación de implementos a cada reserva validando stock.
- **Reporte de gestión**: reservas e ingresos por cancha y periodo, con gráfico de barras y **exportación a CSV**.

## Calidad (ISO/IEC 25010)
- **Seguridad:** contraseñas con `password_hash` / `password_verify` (bcrypt); consultas preparadas PDO (anti inyección SQL);
  escape de salida con `htmlspecialchars` (anti XSS); token CSRF en todos los formularios POST; bloqueo temporal tras 5 intentos fallidos.
- **Eficiencia:** índices en `reservas (id_cancha, fecha)`, `(estado, fecha)` y `(id_usuario)`. Las consultas principales responden en milisegundos con los datos de ejemplo.
- **Fiabilidad:** transacciones al reservar y asignar implementos; manejo de errores de conexión.
- **Mantenibilidad:** comentarios en cada archivo y función, nombres en camelCase (PHP) y snake_case (BD), lógica de negocio separada en `includes/`.

## Estructura
```
index.php, login.php, registro.php, logout.php, dashboard.php
calendario.php, nueva_reserva.php, reservas.php, editar_reserva.php
canchas.php, tipos.php, inventario.php, asignar_implementos.php
reporte.php, exportar_csv.php
includes/   config, db (PDO), auth (sesión/CSRF), funciones, reservas_lib, header, footer
assets/     css/style.css, js/app.js
database/   golapp.sql (esquema + datos de ejemplo)
```
