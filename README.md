# Semestral project for the KIV/PIA-E course at UWB FAS

This repository contains the source files of the semestral project.

## Assignment
Create a web application with the following properties:
- MV* architecture
- API (REST or GraphQL) + documentation
    - unit-tested
- WebSocket communication
- At least two languages
- At least two authentication methods for login
- can be run with single command (preferably docker compose)

## The project
The final project is an extension of a previous semestral project, for the KIV/WEB course. The original application is a pure PHP web application run on the XAMPP stack -- using apache2 as a web server with PHP and MySQL database, implemented under the MVC architecture.

## The original application
The web application serves as a mathematical conference forum with the following user roles and use-cases:
- Anonymous user
    - unlogged user
    - can view the main page and all published articles on the forum
    - can login or register
- Normal users
    - registered via form on the site
    - can view main page and articles
    - can manage their account
    - can add articles, along with name, description and keywords
        - added articles must go through the review process
- Reviewer
    - promoted by admin/superadmin from normal user
    - can do everything a normal user can
    - is assigned to not yet reviewed articles by admins
    - reviews the assigned articles by adding description and numeric evaluation
- Admin
    - promoted by superadmin
    - can do all the previous
    - manages users (promote, ban, change info...)
    - assigns reviewers to unreviewed articles with not enough reviewers (3)
    - reviewes articles previously reviewed by 3 reviewers
        - decides whether to publish, reject or return the article for further reviewing
- SuperAdmin
    - added to the database externally, e.g. at deployment of the web app
    - can do whatever admins can
    - can also manage admins

## Extension
To the baseline application, several extension have been added to comply with the assignment.

### Dockerization
For ease of deployment and isolation, the XAMPP stack has beed implemented using docker containers and deployed with compose. The following containers are used:
- Web container
    - based on php:apache image
    - binds project sources to `httpd`'s root web directory and exposes the WEB port (should be 80)
    - configures the app using environment variables
- MySQL container
    - mysql database, containerized
    - creates the database and user, binds initialization script
    - mounts data to a docker volume
- PHPMyAdmin container
    - for easy access to admins
- WebSocket container
    - for the feature using websockets. Explained later.

An .env file should be created in the root of the project with the following properties: 
- DB_NAME: name of the Database
- DB_USER, DB_PASS: credetinal to the Database
- WEB_PORT: port of the application (recomended 80)
- PMA_PORT: port of PHPMyAdmin
- WSS_HOST: hostname from which the WebSocket server can be reached from outside

Then, the project can be run with `docker compose up [-d]`.

### REST API
A REST API has been added to conform to the assignment. The API is in pure PHP, with the help of the Swagger-PHP library that provides annotations for generation of the OpenAPI documentation/specification. 

The entrypoint of the API lies at location `/api.php`, a call to which returns the YAML documentation. Endpoints are called by specifying the service as a query parameter: `/api.php?service=<endpoint>`. Among the endpoints, parameters can be specified only in two ways: more query parameters (`/api.php?service=<endpoint>&<param>=<value>&...`) or request body (for POST, PUT, DELETE) in the JSON format. All endpoints, except for one, require authorization of user registered via web interface. Each user can generate an API token using the `/api.php?service=get_auth_key` POST endpoint by providing their logging info (login/email and password). This token must then be passed via the `Authorization` HTTP header (as `Authorization: <token>`).

On the server-side, when a request reaches the API controller, the controller first checks whether the service exists, then authorizes the user by the provided API token (except for the `get_auth_key` service) and checks the required request parameters (be it query or body) are provided. Only then is the service called.

The application provides the following services, listed by the use case:
- obtain API token:
    - `get_auth_key` POST request with body parameters specified as JSON: `{'login': <login>, 'pass': <password>}`, optionally with the `'expiration': <duration>` field
    - returns the API key if the user provided correct login info. The key lasts for the duration specified in expiration parameter, or by default 60 minutes
- browsing articles:
    - `get_articles` GET request without parameters
        - return array of all **accepted** articles
        - the articles are represented by a JSON object containing author and article ID, title, desctiption and keywords
    - `show_article` GET request with `id` query parameter, corresponding to the article ID 
        - outputs the contents of the PDF file of the specified article, if found
    - `get_user_articles` GET request with `login` query parameter corresponding to a user's login or email
        - lists all articles (not only approved) of the specified user
- user operations:
    - `get_user` GET request with `login` parameter -- same as above
        - JSON object containing first and last names, email and user ID
        - if the caller is an admin (recognized by the API token associated with the user), the JSON object also contains rights of the user (role) and banned status
    - `ban_users` PUT request with request body being a JSON array of user logins or emails
        - batch-bans users provided in the array
        - can only be called by admins or superadmins (admins can only ban users/reviewers, superadmins can ban admins)
        - return JSON array of successfully banned users
- uploading and deleting articles:
    - `delete_article` DELETE request with `id` query parameter specifying the article
        - deletes the article from DB and FS
            - articles are stored on the filesystem, only article metadata is stored in DB
        - can only delete owned articles -- recognized by API token
            - not even admins can delete other user's articles
    - `add_article` PUT request with JSON body: `{'title': ..., 'descr': <desctiption>, 'key-words': ...}`
        - only adds metadata about an to-be-uploaded article
        - creates a special DB entry without link to file
        - subsequent calls without calling `upload_article` overwrite this special entry, updating the information
    - `upload_article` POST request with body carrying the article PDF file contents
        - an `add_article` call must have been previously made 
            - the special DB entry must exist
        - saves the PDF on the server's FS and links it in the special entry
            - the entry then becomes normal and subsequent uploads must be preceded with another `add_article`
- OTP login:
    - explained below

The full API documentation can be read from the API entrypoint, as stated above... (shows the `web/api-doc.yaml` file)

### OTP login and WebSockets

