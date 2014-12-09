## Update Fields Attach Data

A Joomla CLI script for updating FieldsAttach data, as used for [Starberry](http://starberry.tv/).

### Usage

Clone this repo, or manually add `update-fields.php`, to your Joomla site's `cli` directory.

Used as `php update-fields.php --file file.csv --fieldsMap fields.csv -v`

Where
 
 * `--file` [required] designates the CSV file containing data to import.
 * `--fieldsMap` [required] used to designate a fields mapping file to define the field IDs and the CSV column names they correspond to.
 * `-v` [optional] verbose output
 
### Fields Mapping File
Is a simple two column file with the first row having column names of `fieldid` and `column`. Subsequent rows to contain the related data. Such as:
 
    fieldId,column
    1,office
    2,name
    3,jobtitle
    
Will update/insert the data from the `office` column, of the CSV file, such that it is associated with the article as a FieldsAttach field having an ID of 1. 
