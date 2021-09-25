# api.lk.psk.expert
API personal cabinet for external data Bitrix &amp; 1C

# Описание структуры проекта:

📄*Environment.php* - Файл с константами окружения.   

+ 📂*api* - общий репозиторий;   
   
    + -📂*v1* - версия API;   
        + -📂*controllers*     - Обеспечивают управление REST запросов;   
            + -📄*PartnerController.php*  - Функционал обработчика ответов для запросов на данные по контрагентам;    
            + -📄*UserController.php*     - Функционал для обработки запросов на данные по пользователям;    
        + -📂*managers*        - Каталог классов для работы с данными (выдача/обновление/добавление/удаление);   
            + -📄*Contract.php*           - Класс для взаимодействия с контрактами котрагентов;    
            + -📄*Partner.php*            - Класс для взаимодействия с данными контрагентов;    
            + -📄*User.php*               - Класс для взаимодействия с данными пользователей;    
        + -📂*middleware*         - Директория с промежуточными обработчиками данных;   
            + -📄*AuthMiddleware.php*     - Функционал для обработки данных авторизации;    
        + -📂*models*          - Содержит описание моделей для взаимодействия в пределах используемого окружения API;   
            + -📂*external*      - Содержит описание моделей, используются для внешних данных;   
                + -📄*BaseModelEx.php*      - Абстрактный класс базовой модели внешних данных;    
                + -📄*PartnerEx.php*        - Описание внешней модели данных контрагента;    
                + -📄*StorageDocumentEx.php*- Описание внешней модели данных документов;    
                + -📄*StorageEx.php*        - Описание внешней модели данных склада;    
            + -📂*responses*      - Содержит описание моделей для ответов сервера;   
                + -📄*BaseResponse.php*     - Абстрактный класс базового ответа;    
                + -📄*ErrorResponse.php*    - Класс ответа ошибки;    
                + -📄*Response.php*         - Класс ответа;    
                + -📄*Responses.php*        - Файл с подключаемым функционалом: Ответ сервера в виде ошибки;    
            + -📄*Contract.php*   - Модель представления данных договора (контракта);    
            + -📄*Document.php*   - Модель представления данных документа (связанного с контрактом склада);    
            + -📄*Partner.php*    - Модель данных контрагента;    
        + -📂*service*         - Служебные классы;   
            + -📄*ErrorHandler.php*       - Собственный класс для генерации исключений;    
        + -📄*routes.php*      - Маршруты REST;   
    + -📄*index.php*       - Точка входа в API приложения;    
   
+ -📁*crone* - Каталог со скриптами для crone;    

# Список кодов ошибок окружения:
## 100 - Ошибки инциализации
+ *101* - Неверный тип параметра;