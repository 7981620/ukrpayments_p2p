# Ukrpayments P2P

Пакет предназначен для Laravel 8 и обеспечивает прием платежа через протокол эквайринга с последующим переводом на указанную карту по протоколу Account To Card (A2C) - через платежный шлюз Ukrpayments.

## Установка

###Требования
```
Laravel 8
PHP 8.x
Пакет agenta/stringservice
```

### Добавление в проект

Добавить в composer.json:
```json
    "require": {
      "agenta/ukrpayments_p2p": "dev-master"
      ...
    }
```
и секцию:
```json
  "repositories": [
    {
      "type": "path",
      "url": "packages/*"
    }
  ],
```
Запустить установку:
```bash
composer require agenta/ukrpayments_p2p
```


Копирование конфигурационного файла (config/ukrpayments_p2p.php):
```bash
php artisan vendor:publish --tag=config
```

Запуск миграции (создает таблицу ``` payment_p2_ps ```):
```bash
php artisan migrate
```



## Использование

### 1. Установка переменных

Установить настройки в .env
```dotenv
UPAY_TESTMODE=true # режим тестирования
UPAY_SITE_URL=${APP_URL} # URL сайта - для редиректов из шлюза
UPAY_SITE_URL_TEST="" # URL сайта для тестирования (редиректы из шлюза) 
UPAY_MERCHANT_ID="" # ID мерчанта в шлюзе
UPAY_TERMINAL_ID="" # ID терминала в шлюзе
UPAY_API_TOKEN=""  # токен
UPAY_API_SECRET="" # секретный ключ
UPAY_PAYFORM_ID="" # ID платежной формы в шлюзе
UPAY_MCC="6012" # MCC код
```

### 2. Шаблоны (views)
...

### 3. Тексты сообщений (lang)
...
