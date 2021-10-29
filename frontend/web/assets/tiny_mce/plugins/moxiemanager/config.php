<?php

$_siteRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/');

// General
$moxieManagerConfig['general.license'] = 'XXXX-XXXX-XXXX-XXXX-XXXX-XXXX-XXXX-XXXX';
$moxieManagerConfig['general.hidden_tools'] = '';
$moxieManagerConfig['general.disabled_tools'] = '';
$moxieManagerConfig['general.plugins'] = 'Favorites,History,Uploaded,AutoRename';
$moxieManagerConfig['general.demo'] = false;
$moxieManagerConfig['general.debug'] = false;
$moxieManagerConfig['general.language'] = 'ru';
$moxieManagerConfig['general.temp_dir'] = $_siteRoot . '/data/tinymce/temp';
$moxieManagerConfig['general.cache_dir'] = $_siteRoot . '/data/tinymce/cache_dir';
$moxieManagerConfig['general.http_proxy'] = '';
$moxieManagerConfig['general.allow_override'] = 'hidden_tools,disabled_tools';

// Filesystem
$moxieManagerConfig['filesystem.rootpath'] = $_siteRoot . '/data/upload/';
$moxieManagerConfig['filesystem.include_directory_pattern'] = '/[0-9a-z\-_]/i';
$moxieManagerConfig['filesystem.exclude_directory_pattern'] = '/^(mcith|secret|templates)$/i';
$moxieManagerConfig['filesystem.include_file_pattern'] = '';
$moxieManagerConfig['filesystem.exclude_file_pattern'] = '';
// $moxieManagerConfig['filesystem.extensions'] = 'jpg,jpeg,png,gif,html,htm,txt,docx,doc,zip,pdf';
$moxieManagerConfig['filesystem.extensions'] = 'jpg,jpeg,png,gif,txt,zip,rar,pdf';
$moxieManagerConfig['filesystem.readable'] = true;
$moxieManagerConfig['filesystem.writable'] = true;
$moxieManagerConfig['filesystem.directories'] = [
	// "images" => [
	// 	"upload.extensions" => "gif,jpg,png"
	// ]
];
$moxieManagerConfig['filesystem.allow_override'] = '*';

// Createdir
// $moxieManagerConfig['createdir.templates'] = '/data/upload_files/templates/directory';
$moxieManagerConfig['createdir.templates'] = '';
$moxieManagerConfig['createdir.include_directory_pattern'] = '';
$moxieManagerConfig['createdir.exclude_directory_pattern'] = '';
$moxieManagerConfig['createdir.allow_override'] = '*';

// Createdoc
// $moxieManagerConfig['createdoc.templates'] = 'Index page=/files/templates/document.htm,Normal page=/files/templates/another_document.htm';
$moxieManagerConfig['createdoc.templates'] = '';
$moxieManagerConfig['createdoc.fields'] = 'Document title=title';
$moxieManagerConfig['createdoc.include_file_pattern'] = '';
$moxieManagerConfig['createdoc.exclude_file_pattern'] = '';
$moxieManagerConfig['createdoc.extensions'] = '*';
$moxieManagerConfig['createdoc.allow_override'] = '*';

// Upload
$moxieManagerConfig['upload.include_file_pattern'] = '';
$moxieManagerConfig['upload.exclude_file_pattern'] = '';
// $moxieManagerConfig['upload.extensions'] = '*';
// $moxieManagerConfig['upload.extensions'] = 'gif,jpg,jpeg,png';
$moxieManagerConfig['upload.extensions'] = 'gif,jpg,jpeg,png,zip,rar,pdf,exe';
$moxieManagerConfig['upload.maxsize'] = '5mb';
$moxieManagerConfig['upload.overwrite'] = false;
$moxieManagerConfig['upload.autoresize'] = false;
$moxieManagerConfig['upload.autoresize_jpeg_quality'] = 95;
$moxieManagerConfig['upload.max_width'] = 3000;
$moxieManagerConfig['upload.max_height'] = 3000;
$moxieManagerConfig['upload.chunk_size'] = '5mb';
$moxieManagerConfig['upload.allow_override'] = '*';

// Delete
$moxieManagerConfig['delete.include_file_pattern'] = '';
$moxieManagerConfig['delete.exclude_file_pattern'] = '';
$moxieManagerConfig['delete.include_directory_pattern'] = '';
$moxieManagerConfig['delete.exclude_directory_pattern'] = '';
$moxieManagerConfig['delete.extensions'] = '*';
$moxieManagerConfig['delete.allow_override'] = '*';

// Rename
$moxieManagerConfig['rename.include_file_pattern'] = '';
$moxieManagerConfig['rename.exclude_file_pattern'] = '';
$moxieManagerConfig['rename.include_directory_pattern'] = '';
$moxieManagerConfig['rename.exclude_directory_pattern'] = '';
$moxieManagerConfig['rename.extensions'] = '*';
$moxieManagerConfig['rename.allow_override'] = '*';

// Edit
$moxieManagerConfig['edit.include_file_pattern'] = '';
$moxieManagerConfig['edit.exclude_file_pattern'] = '';
$moxieManagerConfig['edit.extensions'] = 'jpg,jpeg,png,gif,html,htm,txt';
$moxieManagerConfig['edit.jpeg_quality'] = 95;
$moxieManagerConfig['edit.line_endings'] = 'crlf';
// $moxieManagerConfig['edit.encoding'] = 'iso-8859-1';
$moxieManagerConfig['edit.encoding'] = 'utf-8';
$moxieManagerConfig['edit.allow_override'] = '*';

// View
$moxieManagerConfig['view.include_file_pattern'] = '';
$moxieManagerConfig['view.exclude_file_pattern'] = '';
$moxieManagerConfig['view.extensions'] = 'jpg,jpeg,png,gif,html,htm,txt,pdf';
$moxieManagerConfig['view.allow_override'] = '*';

// Download
$moxieManagerConfig['download.include_file_pattern'] = '';
$moxieManagerConfig['download.exclude_file_pattern'] = '';
$moxieManagerConfig['download.extensions'] = '*';
$moxieManagerConfig['download.allow_override'] = '*';

// Thumbnail
$moxieManagerConfig['thumbnail.enabled'] = true;
$moxieManagerConfig['thumbnail.auto_generate'] = true;
$moxieManagerConfig['thumbnail.use_exif'] = false;
$moxieManagerConfig['thumbnail.width'] = 200;
$moxieManagerConfig['thumbnail.height'] = 200;
$moxieManagerConfig['thumbnail.mode'] = "resize";
$moxieManagerConfig['thumbnail.folder'] = 'mcith';
$moxieManagerConfig['thumbnail.prefix'] = 'mcith_';
$moxieManagerConfig['thumbnail.delete'] = true;
$moxieManagerConfig['thumbnail.jpeg_quality'] = 75;
$moxieManagerConfig['thumbnail.allow_override'] = '*';

// Authentication
$moxieManagerConfig['authenticator'] = '';
// $moxieManagerConfig['authenticator'] = 'CustomAuthenticator';

// Local filesystem
$moxieManagerConfig['filesystem.local.wwwroot'] = '';
$moxieManagerConfig['filesystem.local.urlprefix'] = '';
$moxieManagerConfig['filesystem.local.urlsuffix'] = '';
$moxieManagerConfig['filesystem.local.access_file_name'] = 'mc_access';
$moxieManagerConfig['filesystem.local.cache'] = false;
$moxieManagerConfig['filesystem.local.allow_override'] = '*';

// Log
$moxieManagerConfig['log.enabled'] = false;
$moxieManagerConfig['log.level'] = 'error';
$moxieManagerConfig['log.path'] = $_siteRoot . '/data/tinymce/logs';
$moxieManagerConfig['log.filename'] = '{level}.log';
$moxieManagerConfig['log.format'] = '[{time}] [{level}] {message}';
$moxieManagerConfig['log.max_size'] = '100k';
$moxieManagerConfig['log.max_files'] = '10';
$moxieManagerConfig['log.filter'] = '';

// Cache
$moxieManagerConfig['cache.connection'] = 'sqlite:' . '/data/storage/cache.s3db';

// Storage
$moxieManagerConfig['storage.engine'] = 'json';
$moxieManagerConfig['storage.path'] = $_siteRoot . '/data/tinymce/storage';

// AutoFormat
$moxieManagerConfig['autoformat.rules'] = '';
$moxieManagerConfig['autoformat.jpeg_quality'] = 90;
$moxieManagerConfig['autoformat.delete_format_images'] = true;

// AutoRename
$moxieManagerConfig['autorename.enabled'] = true;
$moxieManagerConfig['autorename.space'] = "_";
$moxieManagerConfig['autorename.lowercase'] = true;

// BasicAuthenticator
$moxieManagerConfig['BasicAuthenticator.users'] = [
	["username" => "", "password" => "", "groups" => ["administrator"]]
];

// SessionAuthenticator
$moxieManagerConfig['SessionAuthenticator.logged_in_key'] = 'isLoggedIn';
$moxieManagerConfig['SessionAuthenticator.user_key'] = 'user';
$moxieManagerConfig['SessionAuthenticator.config_prefix'] = 'moxiemanager';

// IpAuthenticator
$moxieManagerConfig['IpAuthenticator.ip_numbers'] = '127.0.0.1';

// ExternalAuthenticator
$moxieManagerConfig['ExternalAuthenticator.external_auth_url'] = 'auth.php';
$moxieManagerConfig['ExternalAuthenticator.secret_key'] = 'A000BC77RU';

// GoogleDrive
$moxieManagerConfig['googledrive.client_id'] = '';

// DropBox
$moxieManagerConfig['dropbox.app_id'] = '';

// OneDrive
$moxieManagerConfig['onedrive.client_id'] = '';

// Amazon S3
$moxieManagerConfig['amazons3.buckets'] = '';

// Azure
$moxieManagerConfig['azure.containers'] = '';

// Ftp
$moxieManagerConfig['ftp.accounts'] = [
	// 'ftpname' => [
	// 	'host' => '',
	// 	'user' => '',
	// 	'password' => '',
	// 	'rootpath' => '/',
	// 	'wwwroot' => '/',
	// 	'passive' => true
	// ]
];

// Favorites
$moxieManagerConfig['favorites.max'] = 20;

// History
$moxieManagerConfig['history.max'] = 20;