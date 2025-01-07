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