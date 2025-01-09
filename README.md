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

## Deployment
To deploy and run, clone the [https://github.com/JustScrub/kiv-pia] git repository on your node, then create `.env` file in the root directory of the repository and setting the following values:
- DB_NAME = name of the database in the MySQL container
- DB_USER = login to the database
- DB_PASS = password to the database
- WEB_PORT = port of the application (recommended 80, 8880 for unit tests)
- PMA_PORT = port of PHPMyAdmin
- WSS_HOST = "publicly" reachable hostname of the websocket server -- accessed by end users! (browsers)

Run `./prj_init.sh` -- installs web app dependencies and runs the containers. Wait until done, might take a while... Coffee time!

To setup the SuperAdmin, register a new user in the web UI, then go to PHPMyAdmin and in the `uzivatel` table under your DB name, change your registered user's `id_pravo` column to value `1`.

## Extensions
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
The website allows One Time Password user verification for sign in. The implementation also uses WebSockets and the API. Users can login with OTP on the `/index.php?page=otp_login` page, which gives instructions on how to carry out the login. The instructions tell the user to POST a request to the `/api.php?service=otp_login` endpoint with a displayed generated JSON body which includes the OTP token. To do this, the user needs to have an account (logically) and a valid API token, obtained with `/api.php?service=get_auth_key`, as discussed above. After the request is sent to the `otp_login` service, the user is automatically logged in.

Under the hood, the web server generates the OTP token and displays it to the user on the OTP login page. But since the web session and API communication are two separate connections, there is a "broker" required that passes data between the two connections. A WebSocket server has been therefore implemented to carry the role of such a broker and runs as one of the containers of this project. While on the OTP login page, the user's browser connects to the WebSocket server and hands it the OTP token, maintaining a connection to it. From the other side, the OTP login API endpoint recieves that OTP token along with the user's API token for authorization. From the API token, the endpoint figures out the user. Then, the endpoint also connects to the WebSocket server, providing the OTP token, user information and a digital signature of the OTP token, so that the message cannot be faked. The WebSocket server recieves this message and based on the matching OTP token (one recieved from browser, the other from API endpoint), sends user information to the browser via the open connection. The JavaScript in the browser then fills out a hidden form with the API data (passed via the WebSocket server) and submits it. The web server then checks the signature and in case of success, logs the user in.

This implementation of OTP does not add another layer of security, it just provides another method of logging in to conform to the assignment. However, it has an interesting feature: in the process, the OTP token does not have to stay secret. If an attacker found its value and tried to log in with it, the victim would log into the attacker's account. As such, only the user's credentials (password) and API token must stay a secret, as usual. This technique could also be used to provide someone else one-time access to one's account without telling them the secrets.

### REST API unit tests
The above described REST API has been unit-tested using python and its pytest and requests packages. The testing scripts can be found in the API_test directory: each numbered python file corresponds to a "Test suite" which examines one API endpoint (or two in case of TS 08). The `common.py` script only contains shared utility functions repeated in the tests (such as obtaining an API token). The `test-article.pdf` file is an arbitrary PDF file that serves for testing article APIs.

Before running the tests, several things have to be done. As this is a quite annoying process, a GitHub Workflow action has been written to test the application. It will be discussed after describing the testing setup process... If you do not wish to go through all of it, just download the test results artifact, as described in a subsection below.

First, the python dependencies must be downloaded by calling `pip install -r requirements` from the API_test directory. Then, a new & fresh project instance must be run. It **must** be a new instance, with no data previously added to the users DB table (even if they were deleted afterward). To ease things up, before actually running the application, run

        sed -i "s|#APITESTSQLSCRIPT|- ./SQL_Scripts/api_test_data.sql:/docker-entrypoint-initdb.d/02.sql|" compose.yaml 

in the root directory of the project. This command substitues the `#APITESTSQLSCRIPT` string in the compose.yaml file for the string between the second and third `|` character of the command. It essentialy tells compose to bins `SQL_Scripts/api_test_data.sql` SQL script that initializes the database for unit tests to the MySQL container. As per [the documentation](https://hub.docker.com/_/mysql) (the *Initializing a fresh instance* section), the container will itself run the script.

Then, the `API_test/test-article.pdf` must be copied three times into `web/Articles/` as `test1.pdf`, `test5.pdf` and `test7.pdf`:

        cp API_test/test-article.pdf web/Articles/test1.pdf
        cp API_test/test-article.pdf web/Articles/test5.pdf
        cp API_test/test-article.pdf web/Articles/test7.pdf

Then, the project can be set up using `docker compose up -d`, and only after all the contaners are deployed and ready can the API be tested. The tests are run by calling `pytest` from the API_test directory. To make it easy call this from the root directory:

        cd API_test && pytest -v | tee result.txt

**THE TESTS WILL DESTROY THE CONFIGURATION**, so they cannot be run multiple times. To run tests again, the whole configuration process must be done from the start, of course including spinning up new containers from scratch...

#### GitHub Workflow test action
... which is the reason a GitHub Workflow action has been setup for testing. The YAML description is at `.github/workflows/test.yaml`. Its sole purpose is to setup an environment for testing and running the unit tests, with outputting the test results as an artifact. The workflow is run when pushing to the 'release' branch (which does not currently exist) or manually.

The test results artifact can be downloaded from GitHub, you just have to be logged in. Go to [https://github.com/JustScrub/kiv-pia/actions], find the last successful workflow run, click on its name, then scroll down and there should be an "artifacts" section. Under it, there is a table of artifact, only containing the 'test-results' artifact. Click the name to download it. GitHub zips it, so unpack the artifact and read the text file...