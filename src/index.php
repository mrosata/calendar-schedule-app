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
    require_once 'inc/site-setup.php';

    // Export the results of the form to calendar?
if (defined('API_EXPORT') && API_EXPORT) {
    // If this is API then we don't need to create any html. Just build all the data here as json
    \copro\do_step('schedule_all');
    \copro\do_step('filter_out_meetings');
    \copro\do_step('compress_meetings');
    header('Content-Type: application/json; charset=UTF-8');
    $all_event_data = \copro\export_meetings_to_json();
    ob_clean();
    echo $all_event_data;
    exit;
}

    // Import previous results from database to export to calendar
if (defined('API_IMPORT') && API_IMPORT) {
    // If this is API then we don't need to create any html. Just build all the data here as json
    header('Content-Type: application/json; charset=UTF-8');
    $event_id = !!\Util\post('dates-event-id') ? \Util\post('dates-event-id') : '';
    $project = !!\Util\post('project') ? \Util\post('project') : null;
    $investor = !!\Util\post('investor') ? \Util\post('investor') : null;
    $projects = !!\Util\post('projects') ? \Util\post('projects') : null;
    $investors = !!\Util\post('investors') ? \Util\post('investors') : null;

    if (!!$investors) {
        // Filter the meetings by an investor id
        $all_event_data = \copro\get_all_database_meetings($event_id, array('investors'=>$investors));
    } elseif (!!$projects) {
        // Filter the meetings by a project id
        $all_event_data = \copro\get_all_database_meetings($event_id, array('projects'=>$projects));
    }
    elseif (!!$investor) {
        // Filter the meetings by amany investor id
        $all_event_data = \copro\get_all_database_meetings($event_id, array('investor'=>$investor));
    } elseif (!!$project) {
        // Filter the meetings by many project id
        $all_event_data = \copro\get_all_database_meetings($event_id, array('project'=>$project));
    }
    else {
        // Get all projets by and event_id
        $all_event_data = \copro\get_all_database_meetings($event_id);
    }

    echo json_encode($all_event_data);
    exit;
}
    
if (defined('FINALIZE') && FINALIZE) {
    // If this is API then we don't need to create any html. Just build all the data here as json
    \copro\do_step('schedule_all');
    \copro\do_step('filter_out_meetings');
    \copro\do_step('compress_meetings');

    $event_id = \EVENT_ID;
    $all_event_data = \copro\export_meetings_to_tqtag();
    
    ob_clean();
    header("Location: https://copro.ezadmin3.com/copro.co.il/originals/miker/calendar/index.html?eventid={$event_id}");
    exit;
}
?><!doctype html>
<html class="no-js" lang="">
<head>
    <?php
    define('ROOT_DIR', __DIR__);
    require_once( 'templates/html-head.php' );

    ?>
</head>
<body>
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
                \Util\show_template( 'the-form.php' );
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
                        <li role="presentation" class="active"><a href="#step1" aria-controls="step 1" role="tab" data-toggle="tab">STEP 1: ROLL OUT!</a></li>
                        <li role="presentation"><a href="#step2" aria-controls="step 2" role="tab" data-toggle="tab">STEP 2: FILTER/MAP</a></li>
                        <li role="presentation"><a href="#step3" aria-controls="step 3" role="tab" data-toggle="tab">STEP 3: COMPRESS</a></li>
                        <li role="presentation" class="debug"><a href="#debug-exceptions" aria-controls="debug-exceptions" role="tab" data-toggle="tab">DUBUG: EXCEPTIONS</a></li>
                        <li role="presentation" class="debug"><a href="#debug-collisions" aria-controls="debug-collisions" role="tab" data-toggle="tab">DUBUG: COLLISIONS</a></li>
                    </ul>

                    <div class="tab-content">
                        <div role="tabpanel" class="tab-pane fade in active" id="step1">
                            <div class="col-sm-12">
                                <h2>Step 1: <small>Roll Out</small></h2>
                            </div>
                            <div class='col-sm-12'>
                                <p>
                                    In this first step we don't account for which investors want to see which projects, we know how many investors there will be and how many projects any investor could possibly meet with. The <strong class="text-danger">Roll Out</strong> schedules each investor to meet with each project without collisions between projects but it <strong class="text-danger">disregards any possible collisions between individual members of a project</strong>.
                                </p>

                            </div>

                            <?php
                            /**
                             * Ok, enough fun and explaining. Let's sort!
                             */

                            echo \copro\do_step('schedule_all', true);
                            ?>

                        </div>
                        <div role="tabpanel" class="tab-pane fade" id="step2">
                            <div class="col-sm-12">
                                <h2>Step 2: <small>Filter Out</small></h2>
                            </div>
                            <div class='col-sm-12'>
                                <p>
                                    In the <strong class="text-danger">Filter Out</strong> step the algorithm walks down the time lines of each investor individually checking whether that investor is meant to meet with any given project. This step uses only a projects ID for comparisons. We are left with a schedule with no collisions between projects (still disregarding project member collisions) and investors. The <strong class="text-danger">Filter Out</strong> step <strong class="text-primary"><em> only checks that an investor meets with all projects in her or his interests list.</em></strong> (<em class="text-warning">Hover over an Investors name to view which projects they are interested in to see the list used to filter their time line</em>)
                                </p>
                            </div>
                            
                            <?php
                            /**
                             *  Let's filter our sorted time_lines!
                             */
                            echo \copro\do_step('filter_out_meetings', true);
                            ?>

                        </div>
                        <div role="tabpanel" class="tab-pane fade" id="step3">
                            <div class="col-sm-12">
                                <h2>Step 3: <small>Compression</small></h2>
                            </div>

                            <div class='col-sm-12'>
                                <p>
                                    In the <strong class="text-danger">Compression</strong> step the algorithm compresses the schedule and at the same time looks for project collisions and project member collisions which would prevent 2 separate projects from being able to present two different investor meetings in a single time slot. This step goes through each time starting at the earliest, if it finds an empty time slot then it will attempt to take the last project scheduled for an investor to meet with and swap it into the empty time slot. At this time it will also ask "is this project already being viewed by another investor in this time slot?" and also "are any crew members from other projects being viewed in this time slot also part of the crew for the project that will be swapped down?". After this step the ordering is complete and we are satisfying all the rules and considerations from above.
                                </p>
                            </div>
                            <br>


                            <?php
                            /**
                             *  Let's compress the mapped time_lines!
                             */
                            echo \copro\do_step('compress_meetings', true);
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
