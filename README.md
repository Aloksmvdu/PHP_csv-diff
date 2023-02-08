# PHP_csv-diff

A simple PHP class that compares two csv files and outputs the difference in json or summary format.

The class assumes that both CSV files are built in spreadsheet style and contain header cells. It uses the given key for both given files to find corresponding row and compares the contents of the files and outputs rows_added, rows_changed, rows_removed, columns_added, columns_removed.

## Usage
```
    $diff = new csv_diff("old.csv", "new.csv", "ID");
    echo $diff->get_diff(PRINT_STYLE_JSON);
```
This assumes that the content of both files contain "ID" cell. The position of the cell in each file however can be different.

**Example:**
```
        OLD_CSV                         NEW_CSV
    "ID","Name","Age"               "ID","Name","Age"   
    "01","Alok","29"                "01","Alok","29"
    "02","Ganesh","30"              "03","Kundan","30"
```

**JSON Output of the above example:**
```
{
    "author": "Alok Yadav",
    "version": "1.0.1",
    "timestamp": 1675853138,
    "_index": [
        "ID"
    ],
    "rows_added": [
        [
            "03",
            "Kundan",
            "30"
        ]
    ],
    "rows_changed": [],
    "rows_removed": [
        [
            "02",
            "Ganesh",
            "30"
        ]
    ],
    "columns_added": [],
    "columns_removed": []
}
```

**Summary Output of the above example:**
```
1 rows removed (50.00%)
1 rows added (50.00%)
0 rows changed (0.00%)
0 columns added (0.00%)
0 columns removed (0.00%)
```
