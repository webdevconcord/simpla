# Модуль ConcordPay для Simpla CMS

Этот модуль для **Simpla CMS** позволит вам принимать платежи через платёжную систему **ConcordPay**.

## Установка

1. Скачайте последнюю версию платёжного модуля.

2. Распакуйте архив на сервере в каталог с установленной **Simpla CMS**.<br>
Пример пути для файлов модуля: *{simpla_root}/payment/ConcordPay*.
   
3. При необходимости установите права доступа для файлов модуля.

4. Зайдите как администратор в **Simpla CMS** и в разделе *«Настройки → Оплата»* нажмите кнопку **«Добавить способ оплаты»**.

5. Из выпадающего меню выберите **Concordpay**, валюта - **Гривны**.

6. Заполните поля модуля данными своего продавца, полученными от платёжной системы:
   - *Идентификатор продавца (Merchant ID)*;
   - *Секретный ключ (Secret Key)*.

7. Отметьте способы доставки, которые можно будет оплатить при помощи данного платёжного метода.

8. Поставьте чекбокс **Активен** и сохраните настройки.

Модуль готов к работе.

*Модуль протестирован для работы с Simpla CMS 2.3.8 и PHP 7.2.*