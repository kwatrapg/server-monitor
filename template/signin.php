<!DOCTYPE html>
<html>
    <head>
        <meta charset="UTF-8">
        <title><?php echo strip_tags ( getConfigValue("app_name") ); ?></title>
        <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
        
        <?php if(file_exists($scriptpath . "/assets/icon.png")) { ?>
            <link rel="shortcut icon" href="assets/icon.png"/>
        <?php } else { ?>
            <link rel="shortcut icon" href="template/assets/icon.png"/>
        <?php } ?>

        <?php if(file_exists($scriptpath . "/assets/icon.png")) { ?>
            <link rel="apple-touch-icon" href="assets/icon-large.png"/>
            <link rel="image_src" href="assets/icon-large.png"/>
        <?php } else { ?>
            <link rel="apple-touch-icon" href="template/assets/icon-large.png"/>
            <link rel="image_src" href="template/assets/icon-large.png"/>
        <?php } ?>

        <link href="template/assets/bootstrap/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" rel="stylesheet" type="text/css" />
        <!-- Theme style -->
		<link href="template/assets/dist/css/AdminLTE.min.css" rel="stylesheet" type="text/css" />
        <link href="template/assets/custom.css" rel="stylesheet" type="text/css" />

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.3.0/respond.min.js"></script>
    <![endif]-->

    </head>

  <body class="login-page">

    <div class="login-box">

      <div class="login-logo">
          <?php if(file_exists($scriptpath . "/assets/logo.png")) { ?>
              <img src="assets/logo.png" class="img-responsive" style="margin: 0 auto;">
          <?php } ?>

          <?php echo getConfigValue("app_name"); ?>
      </div><!-- /.login-logo -->
      <div class="login-box-body">
        <p class="login-box-msg"><?php _e('Sign in to start your session'); ?></p>
        <?php if(!empty($statusmessage)): ?>
				<div class="row"><div class='col-md-12'><div class="alert alert-<?php print $statusmessage["type"]; ?> alert-auto" role="alert"><?php print $statusmessage["message"]; ?></div></div></div>
		<?php endif; ?>
        <form action="?route=signin" method="post">
          <div class="form-group has-feedback">
            <input type="email" name="email" class="form-control" placeholder="<?php _e('Email'); ?>" required autofocus/>
            <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
          </div>
          <div class="form-group has-feedback">
            <input type="password" name="password" class="form-control" placeholder="<?php _e('Password'); ?>" required/>
            <span class="glyphicon glyphicon-lock form-control-feedback"></span>
          </div>
          <div class="row">
            <div class="col-xs-8">

            </div><!-- /.col -->
            <div class="col-xs-4">
              <button type="submit" class="btn btn-primary btn-block btn-flat"><?php _e('Sign In'); ?></button>
            </div><!-- /.col -->
          </div>
		<input type="hidden" name="signin"/>
        </form>

      </div><!-- /.login-box-body -->
      <br><p class="text-center"><a href="?route=forgot"><?php _e('Forgot your password?'); ?></a></p>
    </div><!-- /.login-box -->



    <!-- jQuery -->
    <script src="template/assets/plugins/jQuery/jquery-2.2.3.min.js"></script>
    <!-- Bootstrap 3.3.2 JS -->
    <script src="template/assets/bootstrap/js/bootstrap.min.js" type="text/javascript"></script>

  </body>


</html>
