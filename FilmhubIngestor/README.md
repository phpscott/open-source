# FILMHUB (FH) PACKAGE INGESTOR (FPI)
## The following script will interate over a Filhub Delivery Package directory and parse each YAML into an MRSS item for ingestion.
###### This script is available as is.

## Author: Scott Cliburn
## open-source/FilmhubIngestor

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
    - Build MRSS Import file compliant to zype.com

### The following list of directories are required to be configured.
#### Please note some directories need chmod 777 to be written to or cleaned

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

### The following list of directories are Filmhub Genres (Main)
##### Each of these should be cleaned on a full script run;

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