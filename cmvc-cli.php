<?php
$configPath = __DIR__ . '/../app/config.ini';
if (!file_exists($configPath)) {
    die("Config file not found: app/config.php\n");
}
$config = parse_ini_file(__DIR__ . '/../app/config.ini');

$argumentOptions = array();
$menuOptions["createModel"] = function ($argv, $config) {
    require_once 'DataAccessObject.php';
    require_once 'Service/DataService.php';
    $ds = new CacaoFw\Service\DataService($config);
    $fileName = $argv[2];

    $tableName = strtolower($fileName);
    $fileContent = "<?php\n\nnamespace App\Model;\n\nuse CacaoFw\AbstractModel;\n\nclass $fileName extends AbstractController {\n\n";

    $res = $ds->query("DESCRIBE $tableName");
    $columns = array();
    while ($row = mysqli_fetch_array($res)) {
        $columns[$row['Field']] = $row['Type'];
    }

    foreach ($columns as $key => $fieldName) {
        if ($key != "id") {
            $fileContent .= "    public $$key;\n\n";
        }
    }

    $fileContent .= "\n    public function __construct($" . join(", $", array_keys($columns)) . ") {\n";
    foreach ($columns as $key => $fieldName) {
        $fileContent .= '        $this->' . $key . ' = $' . $key . ";\n";
    }
    $fileContent .= "    }\n}";
    $modelFilePath = __DIR__ . "/../app/src/Model/$fileName.php";
    $file = fopen($modelFilePath, "w");
    fwrite($file, $fileContent);
    fclose($file);

    if ($argv[3] == "--withDao") {
        $fileContent = "<?php\n\nnamespace App\DAO;\n\nuse CacaoFw\DataAccessObject;\n\nclass {$fileName}DAO extends DataAccessObject {\n\n";
        $fileContent .= '    public function __construct($db) {' . "\n        " . 'parent::__construct($db);'. "\n    }\n}";
        $daoFilePath = __DIR__ . "/../app/src/DAO/{$fileName}DAO.php";
        $file = fopen($daoFilePath, "w");
        fwrite($file, $fileContent);
        fclose($file);
    }
};

foreach ($menuOptions as $key => $func) {
    $position = strpos($argv[1], $key);
    if ($position === 2) {
        $func($argv, $config);
        exit();
    }
}
echo "Invalid arguments \n";