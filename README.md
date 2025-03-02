# Symfony API Application

## Описание проекта
1. **Загрузка файлов**.
2. **Авторизация через JWT токен**.
3. **Регистрация пользователей**.
4. **Создание новостей** (свойства: название, автор, контент, фото).
5. **Удаление новостей**.
6. **Получение списка новостей** с пагинацией и фильтрацией.
   
**Настройка переменных окружения:**
  DATABASE_URL="mysql://username:password@127.0.0.1:3306/your_database_name"
  JWT_SECRET_KEY="%kernel.project_dir%/config/jwt/private.pem"
  JWT_PUBLIC_KEY="%kernel.project_dir%/config/jwt/public.pem"
  JWT_PASSPHRASE="your-passphrase"

**Генерация ключей JWT:**
   openssl genrsa -out config/jwt/private.pem -aes256 4096
   openssl rsa -pubout -in config/jwt/private.pem -out config/jwt/public.pem

   ## Доступные маршруты

### 1. Регистрация пользователя
**Маршрут:** `/api/register`  
**Метод:** `POST`  
**Описание:** Регистрирует нового пользователя.  
**Пример тела запроса:**
```json
{
  "email": "user@example.com",
  "password": "securepassword"
}
```
### 2. Авторизация пользователя
**Маршрут:** `/api/auth/login_check`  
**Метод:** `POST`  
**Описание:** Возвращает JWT токен для авторизованного пользователя.  
**Пример тела запроса:**
```json
{
  "email": "user@example.com",
  "password": "securepassword"
}
```

### 3. Загрузка файлов
**Маршрут:** `/api/upload`  
**Метод:** `POST`  
**Описание:** Загружает файл на сервер.  

### 4. Создание новости
**Маршрут:** `/api/news`  
**Метод:** `POST`  
**Описание:** Создает новую запись о новости.  
**Пример тела запроса:**
```json
{
  "title": "Новость",
  "author": "Автор",
  "content": "Контент новости",
  "photo": "файл"
}
```

### 5. Удаление новости
**Маршрут:** `/api/news/{id}`  
**Метод:** `DELETE`  
**Описание:** Удаляет новость по указанному идентификатору.

### 6. Получение списка новостей
**Маршрут:** `/api/news`  
**Метод:** `GET`  
**Описание:** Возвращает список новостей с поддержкой пагинации и фильтрации.  
**Пример параметров запроса:** `/api/news?page=1&author=Автор`.
