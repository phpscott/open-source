# FILMHUB (FH) PACKAGE INGESTOR (FPI)
#### Filhmub > MRSS Transformer
This script is available as is and without warranty.

- Author: Scott Cliburn
- open-source/FilmhubIngestor

**Script Flow:**

When the script is run, a Configuration file is loaded from conf/.
The configuration is a JSON file that represents the paths/uris where content will be reflected in the MRSS.

- **Step 1:** Read in a directory list of the aws bucket, saved as a flat file to: dirFile in DIRLIST

- **Step 2:** Iterate over the directory list and identify YAML files.

- **Step 3:** Extract/Download YAML file and store locally as YAML and JSON, build objects file with yaml ids/file paths

- **Step 4:** Read in Object file and iterate over it, building XML items (movies/episodes, trailers, series)
    - Detailed mapping data in external document.
    - Fields mapped into <item> elements stored as JSON for final iteration

- **Step 5:** Read in each directory of items (movies/episodes, trailers, series). 
    - Iterate over each and convert from json into xml and store inside genre folder (Filmhub genres used as primary category).
    - Do this with trailers and series objects (parent to episodes) and build as individual items for mapping post ingest

- **Step 6:** Read in each genre directory and iterate over each file (represents a single video)
    - Build MRSS Import file compliant to https://support.zype.com/hc/en-us/articles/115011037147-MRSS-Feed-Import

#### The following list of directories are required to be configured.
`Please note some directories need chmod 777 to be written to or cleaned`

> UPDATE: A new "build" action is available to create the directories required in the data folder. This requires the data folder to exist and be writable: `chmod 777 data`

- These directories should be placed in a directory called `data/default`.
- The default folder will correlate directly to the default.json config file and should be named the same.
- If your config file is "myconfig" you should name the data/default folder to data/myconfig.
- Then create the following directories in that data/myconfig folder:
    - **DIRLIST**
        - "dirlist/", keep untouched
    - **YMLJSON**
        - "yaml_json/", // clean on full run
    - **YAML**
        - "yaml/", // clean on full run
    - **XMLITEMS**
        - "xmlitems/", // clean on full run
    - **XMLSERIESITEMS**
        - "xmlitems_series/", // clean on full run
    - **XMLTRAILERITEMS**
        - "xmlitems_trailers/", // clean on full run
    - **OBJECTS**
        - "objects/", // clean on full run
    - **SKUIDS**
        - "skuids/", // clean on full run
    - **BADSKUIDS**
        - "badskuids/", // clean on full run
    - **MRSSIMPORT**
        - "mrssimport/", // clean on full run
    - **POSTINGEST**
        - "postingest/" // clean on full run

#### The following list of directories are Filmhub Genres (Main)

These contain individual items for each Filmhub type (series/single work).

See: conf/default.json for an example.

Each of these should be cleaned on a full script run.

This section was extracted directly from the conf/default.json example.

- Each of these folders need to be created in the mrssimport folder created above:
    - "Action/Adventure": "mrssimport/actionadventure/",
    - "Animation": "mrssimport/animation/",
    - "Comedy": "mrssimport/comedy/",
    - "Crime": "mrssimport/crime/",
    - "Documentary": "mrssimport/documentary/",
    - "Fantasy": "mrssimport/fantasy/",
    - "Horror": "mrssimport/horror/",
    - "Informational & Educational": "mrssimport/infoandedu/",
    - "Musical/Dance": "mrssimport/musicaldance/",
    - "Music & Performances": "mrssimport/musicperformance/",
    - "Mystery": "mrssimport/mystery/",
    - "Reality Show": "mrssimport/realityshow/",
    - "Sci-Fi": "mrssimport/scifi/",
    - "Sport & Fitness": "mrssimport/sportsfitness/",
    - "Thriller": "mrssimport/thriller/",
    - "Unknown": "mrssimport/unknown/",
    - "War": "mrssimport/war/",
    - "Western": "mrssimport/western/"

### Running the Script:

#### Commands to run the script ####

The script is run on the command line and requires 2 arguments to be passed with values.

The arguments or parameters are:

- `-c filename`: This is the name of the **configuration** JSON file without the extension: `-c default`
- `-a action`: This is the **action** that should be performed when running the script:  `-a objects`

The following actions are available and must be run in order:

1. "clean": This will clean all directories that are cleaned on a full run.
2. "reddir": This will read the directory flat file that represents an S3 Bucket, and build paths/skuids.
3. "objects": This will read the skuids and validate them, and pull down YAML files for each skuid (single works/series)
4. "assets": This will parse the YAML files for each skuid and build json with mrss field names.
5. "xmlitems": This will take the json with mrss field names and create actual MRSS <item>s and store them as XML.
6. "buildmrss": This will take the MRSS items in XML format and build complete MRSS files based on Filmhub Genres (main).

- "all": This runs all the above events.
- "build": This will create directories in the data folder. This requires the data folder to be `chmod 777`.

**Example Run:**

`php fpi.php -c default -a all`

> NOTE: You should consider piping the output to a log file. There are many informative echo statements that can be used for auditing.

`php fpi.php -c default -a all > mylog.log`

#### Modules Installed ####

The script was built to be run on the command line via a Linux system.

The following modules are installed on the working Linux system. 

**Bolded** are items that should be verified as installed for this script to work.

**[PHP Modules]**
- bz2
- calendar
- Core
- ctype
- **curl**
- **date**
- dom
- exif
- FFI
- fileinfo
- filter
- ftp
- gd
- gettext
- hash
- iconv
- **json**
- **libxml**
- mbstring
- mysqli
- mysqlnd
- openssl
- pcntl
- pcre
- PDO
- pdo_mysql
- Phar
- posix
- readline
- Reflection
- session
- shmop
- SimpleXML
- sockets
- sodium
- SPL
- standard
- sysvmsg
- sysvsem
- sysvshm
- tokenizer
- xml
- xmlreader
- xmlwriter
- xsl
- **yaml**
- Zend OPcache
- zip
- zlib

**[Zend Modules]**
- Zend OPcache

**Op Sys: `lsb_release -a`**
- Distributor ID: Ubuntu
- Description:    Ubuntu 22.04.1 LTS
- Release:        22.04
- Codename:       jammy
