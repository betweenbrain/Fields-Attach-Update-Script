## Update Fields Attach Data

A Joomla CLI script for updating FieldsAttach data, as used for [Starberry](http://starberry.tv/).

### Usage

Clone this repo, or manually add `update-fields.php`, to your Joomla site's `cli` directory.

Used as `php update-fields.php --file file.csv --fieldsMap fields.csv -v`

Where
 
 * `--file` [required] designates the CSV data file containing data to import.
 * `--fieldsMap` [required] designates the fields mapping file to define the field IDs and the CSV column names they correspond to.
 * `-v` [optional] verbose output
 
### Fields Mapping File
Is a simple two column file that  **must** begin with with a header row with the column names of `fieldid` and `column`. Subsequent rows to contain the related data. Such as:
 
    fieldId,column
    1,office
    2,name
    3,jobtitle

See the included `map.csv` file as an example.

Will update/insert the data from the `office` column, of the CSV file designated with `--file`, into the FieldsAttach field having an ID of 1.

### Data File
The data file **must** also begin with with a header row with the column names matching those specified in the rows of the fields mapping file. The data file **must** also have an `Article Id` column, in which each row specifies the Joomla article ID the imported data is to be associated with.  

See the included `data.csv` file as an example.