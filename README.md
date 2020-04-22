# bifrost_db
Database Connection and Query Class

A simple database connection and query class that prepares all queries entered into it and allows for easy collection of data.

A config array is required to be passed into it, this should contain the pass to the INI file that contains the database connection details.

This was chosen so that the INI file could be kept in a secure location on the webserver, but its easily changed to hardcoded details by manually populating the $aInfo variable.

The class can then be initalised with:

`$oBifrost = new BiFrost($aConfig);`

If the connection fails, a FALSE flag is returned.

Queries should be packaged into an array with two keys, `QUERY` and `PARAMS`, which should also be an array.  Example below:

`$aQuery['query'] = '
    SELECT
        *
    FROM
        table
    WHERE
        name = ?;
$aQuery['params] = ['James'];`

A single row can then be returned with:

`$aResult = $oBifrost->prepQuery($aQuery)->fetchRow();`

The result array will contain two main keys, `STATUS` and `PAYLOAD`.  If the query failed, `STATUS` will be `FALSE` and `PAYLOAD` will contain the error.  If the query was successful, `STATUS` will be `TRUE` and `PAYLOAD` will contain the result set.

Multiple rows can be returned by calling the `fetchRows()` method.
