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
      <h1>Internal Server Error</h1>
      <?php if($debug):?>

      <h3><?=$exmsg;?></h3>
      <table class="table table-bordered">
          <?php foreach($stackTrace as $stack):?>
            <tr>
          <td><?=$stack['file'];?></td>
          <td><?=$stack['line'];?></td>
          <td><?=$stack['function'];?></td>
        </tr>
          <?php endforeach;?>
        </table>
      <pre><?php foreach ( $sql as $query ) { echo "$query\n"; } ?></pre>
      <?php else:?>
      <h3>Oops, something went wrong!</h3>
      <?php endif;?>


    </div>
  </div>
</body>
</html>