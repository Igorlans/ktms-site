<?php
define('WP_CACHE', true); // WP-Optimize Cache
/**
 * Основные параметры WordPress.
 *
 * Скрипт для создания wp-config.php использует этот файл в процессе
 * установки. Необязательно использовать веб-интерфейс, можно
 * скопировать файл в "wp-config.php" и заполнить значения вручную.
 *
 * Этот файл содержит следующие параметры:
 *
 * * Настройки MySQL
 * * Секретные ключи
 * * Префикс таблиц базы данных
 * * ABSPATH
 *
 * @link https://ru.wordpress.org/support/article/editing-wp-config-php/
 *
 * @package WordPress
 */
// ** Параметры MySQL: Эту информацию можно получить у вашего хостинг-провайдера ** //
/** Имя базы данных для WordPress */
define( 'DB_NAME', 'ktms' );
/** Имя пользователя MySQL */
define( 'DB_USER', 'root' );
/** Пароль к базе данных MySQL */
define( 'DB_PASSWORD', '' );
/** Имя сервера MySQL */
define( 'DB_HOST', 'localhost' );
/** Кодировка базы данных для создания таблиц. */
define( 'DB_CHARSET', 'utf8mb4' );
/** Схема сопоставления. Не меняйте, если не уверены. */
define( 'DB_COLLATE', '' );
/**#@+
 * Уникальные ключи и соли для аутентификации.
 *
 * Смените значение каждой константы на уникальную фразу.
 * Можно сгенерировать их с помощью {@link https://api.wordpress.org/secret-key/1.1/salt/ сервиса ключей на WordPress.org}
 * Можно изменить их, чтобы сделать существующие файлы cookies недействительными. Пользователям потребуется авторизоваться снова.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         'Yu?3A28}AlB*/v-Jm9IYhir=o |B4KejfgR(8k8KJIC+q)sKBPqMrQAHhks_2>>I' );
define( 'SECURE_AUTH_KEY',  'xvBHfs)@K</Qg/oNVR.i4L)[,/?CHOSrYqmbI~PK+!V-2W$Zs3=)|pJ2U/3N|G}G' );
define( 'LOGGED_IN_KEY',    'ghMCIuY:r#kbW_Hv4g0f35IWj;KyS_QSMimW1kVRZ93>7[=:cP?rfQVp)Cir<N]&' );
define( 'NONCE_KEY',        'jRCjpWdV8B/RcKS1SpIwp/$Eq}ZK,JVbo]oq,W>9wj`%7]@X65$Ov<H$,i|Z]dCh' );
define( 'AUTH_SALT',        'C};D.(e~L-){CkgWQ(Qs[RB9pT_G Wv`_8ycmf|9?7)kXHV:M%%xt~fib5<cm/Qg' );
define( 'SECURE_AUTH_SALT', 'f^;ehMxd#I}@<ZcexI9t%I#d^^lKL;<_nQ1!Ec2-|K[8u}k|kd(,9bc+^6|ds;@y' );
define( 'LOGGED_IN_SALT',   '`[#rHf!,OE0TYn^h)s_g^}5@Tupy,~xJ,gK)-+Jq/$CYQ@lH;9H2#RL.dEI=|qc@' );
define( 'NONCE_SALT',       'cFCasUn-lw4Au|$Ap,^7KhM1nnDE4EG9WqH8O3ch-{!A6XLvwRqJGLh}yj]22,/h' );
/**#@-*/
/**
 * Префикс таблиц в базе данных WordPress.
 *
 * Можно установить несколько сайтов в одну базу данных, если использовать
 * разные префиксы. Пожалуйста, указывайте только цифры, буквы и знак подчеркивания.
 */
$table_prefix = 'wp_';
/**
 * Для разработчиков: Режим отладки WordPress.
 *
 * Измените это значение на true, чтобы включить отображение уведомлений при разработке.
 * Разработчикам плагинов и тем настоятельно рекомендуется использовать WP_DEBUG
 * в своём рабочем окружении.
 *
 * Информацию о других отладочных константах можно найти в документации.
 *
 * @link https://ru.wordpress.org/support/article/debugging-in-wordpress/
 */
define( 'WP_DEBUG', true );
/* Это всё, дальше не редактируем. Успехов! */
/** Абсолютный путь к директории WordPress. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
/** Инициализирует переменные WordPress и подключает файлы. */
require_once ABSPATH . 'wp-settings.php';