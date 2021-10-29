# Website framework

Особенности:
 - Поддержка двух независимых приложений: 'frontend' (публичная часть сайта) и 'backend' (админпанель);
 - Ядро из PSR-15 middlewares;
 - PSR-7 Http messages;
 - PSR-14 Event Dispatcher;
 - PSR-16 Caching;
 - Портированы компоненты Html, ActiveForm из Yii2 (при минимальной доработке возможен запуск любых Yii2 виджетов);
 - ORM компонент Eloquent из Laravel;
 - Bootstrap 4;
 - Валидация моделей и форм;
 - Данные хранятся в отдельной папке '/data/' и не включаются в репозиторий.

Технические требования:
 - PHP 8
 - MySQL 5.7

Развёртывание:
1. Клонировать репозиторий.
2. Установить зависимости через composer, командой `composer install`. При развёртывании на хостинге, возможно сообщение о несоответствии требуемой версии php. В таком случае возможны варианты:
 - запускать команду с опцией `--ignore-platform-reqs`;
 - папку /vendor/ загрузить с локальной машины на хостинг через FTP.
3. При разворачивании проекта для локальной разработки, править только конфиги `main-local.php`. Чтобы не заливать (push) в удалённый репозиторий свои конфиги с паролями, перед первым коммитом, для файлов локальных настроек выполнить команду `git update-index --assume-unchanged path/to/file` (или выбрать соответствующую команду меню если используется клиент Windows, например TortoiseGit). После этого локально измененные конфиги не будут считаться измененными.
4. Для сборки CSS стилей сайта из SASS исходников:
 - установить node.js;
 - установить Gulp глобально в систему `npm i gulp -global`;
 - выполнить команду `npm install` в корневой директории сайта, чтобы установить необходимые для сборки пакеты;
 - для сборки отдельных частей запускать `gulp sass_frontend` и т.п. (см. папку /static/tasks/);
 - для автоматической сборки стилей при изменении исходников запустить задачу `gulp watching`.

 P.S. Репозиторий для демонстрации. Код использовался в реальных проектах, удалено всё относящееся к специфическим реализациям бизнес логики и вёрстки (в папке /customer/).