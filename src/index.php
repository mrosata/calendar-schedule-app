<?php
/**
 *  Index for CoPro
 */

    ini_set('display_errors', 1);
    error_reporting(E_ALL);
    ini_set('output_buffering', 1);
    header("Access-Control-Allow-Origin: *");
    //header("Content-Type: application/x-www-form-urlencoded; charset=UTF-8");
    mb_internal_encoding("UTF-8");
    session_start();
    require_once 'inc/improved-site-setup.php';

    $output = \copro\Config::$output;
?>
<!doctype html>
<html class="no-js" lang="">
<head>
    <?php
    define('ROOT_DIR', __DIR__);
    require_once( 'templates/html-head.php' );
    ?>
</head>
<body>
<div class="loading-animation">
    <!-- Holds the loading animation -->
    <div class="item"></div>
    <div class="item"></div>
</div>
<div class="inner-body">
    <div class="container">
        <div class="row expanded">

            <?php
            \Util\show_template( 'nav-header.php' );
            ?>

        </div>

        <div class="row">
            <!-- Here are the site options -->
            <div class="col-sm-12 col-md-8">

                <form action="https://copro.ezadmin3.com/copro.co.il/originals/miker/calendar/index.html" method="get" name="event-id-input" class="form" accept-charset="UTF-8">

                    <fieldset>
                        <!-- Event ID -->
                        <div class="form-group">
                            <legend>
                                <h4>Already started? <small>Enter an EventID</small>:</h4>
                            </legend>
                            <label for="eventid">
                                <input type="text" name="eventid" class="form-control">
                            </label>
                        </div>

                    </fieldset>
                    <button type="submit" class="btn success">Get Meeting Schedule!</button>
                </form>

            </div>

        </div>
        <div class="row">

            <div class="col-sm-12">
                <?php
                \Util\show_template( 'improved-form.php' );
                ?>

            </div>

        </div>


        <!-- HERE IS THE TABS WITH THE ACTUAL CALENDAR APP -->
        <!-- HERE IS THE TABS WITH THE ACTUAL CALENDAR APP -->
        <div class="row expanded">
            <div class='col-sm-12'>
                <div class="algorithm-tabs-container">

                    <!-- Nav tabs -->
                    <ul class="nav nav-tabs" role="tablist">
                        <li role="presentation" class="active"><a href="#meetings" aria-controls="meetings" role="tab" data-toggle="tab">Meetings</a></li>
                        <li role="presentation" class="debug"><a href="#debug-exceptions" aria-controls="debug-exceptions" role="tab" data-toggle="tab">DUBUG: EXCEPTIONS</a></li>
                        <li role="presentation" class="debug"><a href="#debug-collisions" aria-controls="debug-collisions" role="tab" data-toggle="tab">DUBUG: COLLISIONS</a></li>
                    </ul>

                    <div class="tab-content">
                        <div role="tabpanel" class="tab-pane fade in active" id="step1">
                            <div class="col-sm-12">
                                <h2>Meetings: <small>Roll Out</small></h2>
                            </div>
                            <div class='col-sm-12'>
                                <p>

                                </p>

                            </div>

                            <?php
                            /**
                             * Ok, enough fun and explaining. Let's sort!
                             */

                            echo $output;
                            ?>

                        </div>

                        <!-- Exceptions Debug -->
                        <div role="tabpanel" class="tab-pane fade" id="debug-exceptions">
                            <div class="col-sm-12">
                                <h2>Debug: <small>Exceptions</small></h2>
                            </div>

                            <div class="col-sm-12">
                                <p>

                                </p>
                            </div>
                            <br>
                            <?php global $debug_exceptions; ?>
                            <?php echo $debug_exceptions; ?>
                        </div>


                        <!-- Debug collisions -->
                        <div role="tabpanel" class="tab-pane fade" id="debug-collisions">
                            <div class="col-sm-12">
                                <h2>Debug: <small>Collisions</small></h2>
                            </div>

                            <div class="col-sm-12">
                                <p>

                                </p>
                            </div>
                            <br>
                            <?php global $debug_collisions; ?>
                            <?php echo $debug_collisions; ?>
                        </div>


                    </div> <!-- .tabbed-content -->


                </div> <!-- .tabbed-content -->


            </div>  <!-- .algorithm-tabs-container -->
            </div>  <!-- .col-sm-12 -->
        </div>


    </div>
</div><!-- .inner-body.container -->
<hr>
<div class="container">
    <br>

    <div class="row">
        <div class="col-sm-12">
            <img src="//unsplash.it/900/400/?random" alt="Random Image" class="img-responsive img-thumbnail center-block">
        </div>

        <div class="col-sm-12">

        </div>
    </div>
</div>

<hr>

<footer class="footer container-fluid">
    <div class="panel panel-primary">
        <div class="well well-sm">
            <div class="row">
                <div class="col-sm-2 col-md-2">
                    <span><strong class="text-danger">TQ-SOFT</strong></span>
                </div>
                <div class="col-sm-5 col-sm-push-5 col-md-3 col-md-push-7">
                    <span><strong class="text-primary">By: Michael Rosata</strong></span>
                </div>
            </div>

        </div>
    </div>
</footer>

<script src="js/plugins.js"></script>

<!-- Google Analytics: change UA-XXXXX-X to be your site's ID. -->
<script>
    (function(b,o,i,l,e,r){b.GoogleAnalyticsObject=l;b[l]||(b[l]=
        function(){(b[l].q=b[l].q||[]).push(arguments)});b[l].l=+new Date;
        e=o.createElement(i);r=o.getElementsByTagName(i)[0];
        e.src='https://www.google-analytics.com/analytics.js';
        r.parentNode.insertBefore(e,r)}(window,document,'script','ga'));
    ga('create','UA-XXXXX-X','auto');ga('send','pageview');
</script>
</body>
</html>
