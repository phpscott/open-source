# MRSS Conversion Tool
#### MRSSConversion > MRSS Transformer
This script is available as is and without warranty.

- Author: Scott Cliburn
- open-source/MRSSConversion

### OVERVIEW    
The scripts privided here will read in a complete release of Filmhub Single Work and Series Packages in YAML and map them to the Zype MRSS Import schema.
- https://support.zype.com/hc/en-us/articles/115011037147-MRSS-Feed-Import

The script will iterate over a file output of directory listings of an S3 Bucket and download and parse the YAML which includes:
- metadata
- images
- video
- closed caption files


#### SETUP
To use this script follow the below steps to get started

- Copy the mcon.php file into a directory.
- Create a directory next to the php file called "conf"
- Create a directory next to the php file called "s3conf"
- Copy the "conf/defaut.json" from the repo into the "conf" directory.
- Copy the "s3conf/defaut.json" from the repo into the "s3conf" directory.
- Update the "conf/defaut.json" you copied down to match your settings/paths. 
- Update the "s3conf/defaut.json" you copied down to include your aws settings. 
    - Note: The bucket is defined on the "conf/default.json" file, and can also be passed as an option to the script.
- Run the sysinit flag to verify you have the required modules installed.

**Script Flow:**

When the script is run, a Configuration file is loaded from conf/.
The configuration is a JSON file that represents the paths/uris where content will be reflected in the MRSS.

- **Step 1:** Read in a directory list of the aws bucket, saved as a flat file to: dirFile in DIRLIST

> UPDATE: This script can now be configured to use the AWS CLI to generate the dirFile.
> See S3 Actions below and Examples Runs for more details.

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

> UPDATE: A new "build" action is available to create the directories required in the data folder. This requires the data folder to exist and be writable: `chmod -f 0777 data`

- These directories should be placed in a directory called `data/default`. (Replace default with your desired directory).
- The default folder will correlate directly to the default.json config file and should be named the same.
- If your config file is "myconfig" you should name the data/default folder to data/myconfig.
- Then create the following directories in that data/myconfig folder (or run the `-a build` action):
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
    - **MRSSITEMS**
        - "mrssitems/", // clean on full run
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
    - "Action/Adventure": "mrssitems/actionadventure/",
    - "Animation": "mrssitems/animation/",
    - "Comedy": "mrssitems/comedy/",
    - "Crime": "mrssitems/crime/",
    - "Documentary": "mrssitems/documentary/",
    - "Fantasy": "mrssitems/fantasy/",
    - "Horror": "mrssitems/horror/",
    - "Informational & Educational": "mrssitems/infoandedu/",
    - "Musical/Dance": "mrssitems/musicaldance/",
    - "Music & Performances": "mrssitems/musicperformance/",
    - "Mystery": "mrssitems/mystery/",
    - "Reality Show": "mrssitems/realityshow/",
    - "Sci-Fi": "mrssitems/scifi/",
    - "Sport & Fitness": "mrssitems/sportsfitness/",
    - "Thriller": "mrssitems/thriller/",
    - "Unknown": "mrssitems/unknown/",
    - "War": "mrssitems/war/",
    - "Western": "mrssitems/western/"

### Running the Script:

#### Commands to run the script ####

The script is run on the command line and requires 2 arguments to be passed with values.

The arguments or parameters are:

- `-c filename`: This is the name of the **configuration** JSON file without the extension: `-c default`
- `-a action`: This is the **action** that should be performed when running the script:  `-a objects`
- `-s session`: This is the **session** that was previously run and will be resued:  `-s session`
- `-b bucket`: This is the **bucket** param that can override the s3 conf:  `-b bucket name` 

The following actions are available and must be run in order:

1. "build": This will build all directories that are required on a script run.
2. "clean": This will clean all directories that are cleaned on a full run.
3. "reddir": This will read the directory flat file that represents an S3 Bucket, and build paths/skuids.
4. "objects": This will read the skuids and validate them, and pull down YAML files for each skuid (single works/series)
5. "parseyaml": TBD
6. "cleanyaml": TBD
7. "assets": This will parse the YAML files for each skuid and build JSON with mrss field names.
8. "xmlitems": This will take the JSON with MRSS field names and create actual MRSS <item>s and store them as XML.
9. "buildmrss": This will take the MRSS items in XML format and build complete MRSS files based on Filmhub Genres (main).
10. "pullcaptions": TBD

**Additional Actions**

- "full": This runs all the above events.
- "fulls3": This runs all the above events and uses the aws-cli s3 command to do it.
- "build": This will create directories in the data folder. This requires the data folder to be `chmod 777`.
- "sysinit": This will do a quick check on if certain functions that are used in this script are available.

**S3 Actions**

Some of the following actions will require the AWS CLI to be installed. 

- "s3config": This will load the S3 Configuration. This is called with each additional action automatically. This is session based.
- "sets3env": This will load the S3 Conf file and set the Environment Variables using that data.
- "gets3env": This will load the current Env. Vars. into the GLOBAL `s3_env_config`.
- "s3list": This action will list the s3 bucket contents.

**Example Runs:**

`php mcon.php -c default -a sysinit` - simple check with RED/GREEN color codes: ERROR/PASS

`php mcon.php -c default -a clean` - cleans the default data directories and files.

`php mcon.php -c default -a build` - checks and creates directories that are not created.

`php mcon.php -c default -a fulls3` - requires `new_data_onrun` to be set to `true`

`php mcon.php -c default -a fulls3 -s 20230502111037` - ignores `new_data_onrun`

`php mcon.php -c default -a fulls3 -s 20230502111037 -b zype-filhmub` - Uses a predefined session, ignoring s3 lookup and uses an alternate AWS Bucket.

`php mcon.php -c default -a full -s 20230502111037` - Uses the predefined session, cleans and looks for a existing dir file.

> NOTE: You should consider piping the output to a log file. There are many informative echo statements that can be used for auditing.

`php mcon.php -c default -a fulls3 > mylog.log`

**Complete Run Example: Individual Calls**

> NOTE: You should have pulled down the repo, and have created a directory called data with a chmod 0777 already

> NOTE: You should have also created the configuration file using the default.txt example in conf/ already

`php mcon.php -c mirrordog -a sysinit`

`php mcon.php -c mirrordog -a build`

`php mcon.php -c mirrordog -a clean`

> NOTE: You should upload the directory list file at this time: name it to match this config value: dirFile (remove the .txt and it must have a .txt extension)

`php mcon.php -c mirrordog -a readdir`

`php mcon.php -c mirrordog -a objects`

`php mcon.php -c mirrordog -a cleanyaml`

`php mcon.php -c mirrordog -a parseyaml`

`php mcon.php -c mirrordog -a assets`

`php mcon.php -c mirrordog -a xmlitems`

`php mcon.php -c mirrordog -a buildmrss`

`php mcon.php -c mirrordog -a pullcaptions`

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

**`aws --version`**
aws-cli/2.9.21 Python/3.9.11 Linux/5.15.0-58-generic exe/x86_64.ubuntu.22 prompt/off
