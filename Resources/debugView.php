<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta http-equiv="Content-Language" content="en-gb" />
<title>Cacao MVC</title>
<!-- Latest compiled and minified CSS -->
<link rel="stylesheet"
  href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css">

<!-- Optional theme -->
<link rel="stylesheet"
  href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap-theme.min.css">
</head>
<body>
  <div class="row">
    <div class="col-sm-10 col-sm-offset-1">
      <h1>Debug View</h1>
      <h3>Request Params</h3>
      <pre>
        <?php var_dump($params);?>
      </pre>
      <h3>SESSION Variables</h3>
      <pre>
        <?php var_dump($_SESSION);?>
      </pre>
      <h3>COOKIE Variables</h3>
      <pre>
        <?php var_dump($_COOKIE);?>
      </pre>
      <h3>POST Variables</h3>
      <pre>
        <?php var_dump($_POST);?>
      </pre>
      <h3>GET Variables</h3>
      <pre>
        <?php var_dump($_GET);?>
      </pre>
      <h3>SERVER Variables</h3>
      <pre>
        <?php var_dump($_SERVER);?>
      </pre>
    </div>
  </div>
</body>
</html>