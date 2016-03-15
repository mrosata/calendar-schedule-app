<?php
/**
 *  Index for CoPro
 */
?><!doctype html>
<html class="no-js" lang="">
<head>
    <?php
    ini_set('display_errors', 1);
    ini_set('output_buffering', 1);
    error_reporting(E_ALL);
    define('ROOT_DIR', __DIR__);

    require_once( 'templates/html-head.php' );
    require_once 'inc/site-setup.php';

    ?>
</head>
<body>
<div class="inner-body container">
    <div class="container">
        <div class="row expanded">

            <?php
            \Util\show_template( 'nav-header.php' );
            ?>


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
                        <li role="presentation"><a href="#collisions" aria-controls="step 3" role="tab" data-toggle="tab">AVOIDED COLLISIONS</a></li>
                        <?php if (!!\Util\post('calendar-id')): // CALENDAR OUTLOOK CREATE REAL EVENTS !!! ?>
                            <li role="presentation"><a href="#outlook-events" aria-controls="Create Events" role="tab" data-toggle="tab">OUTLOOK EVENTS</a></li>
                        <?php endif ?>

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

                        <div role="tabpanel" class="tab-pane fade" id="collisions">
                            <div class="row">
                                <div class="col-sm-12">
                                    <h2>Avoided: <small>Crew conflicts spotted via Email-collision</small></h2>
                                </div>

                                <div class='col-sm-12'>

                                    <p>
                                        These are all scheduling conflicts that arise when 2 projects scheduled for the same time slot are comprised of one or more of the same people. <strong class="text-danger">Step 3</strong> took care of these collisions when compressing, this tab just shows the collisions between different projects and their members which would have made trouble in the schedule. So if the director of project 10 had also been a producer of project 13 then those 2 projects would not be able to meet in the same time slot. This is determined by the usage of email addresses since the members of a project don't all have user-ids with the website running algorithm. The list below shows collisions that have been handled.
                                    </p>
                                </div>
                                <br>

                                <div class="col-sm-12">
                                    <p>
                                        <strong class='label label-danger'>Prevented Email Collision:</strong>
                                    </p>
                                    <br>
                                </div>
                                <br>

                                <?= $collisions ?>
                            </div>
                        </div>

                        <?php if (!!\Util\post('calendar-id') || !!\Util\post('export-calendar') == 'on'): // CALENDAR OUTLOOK CREATE REAL EVENTS !!! ?>
                        <div role="tabpanel" class="tab-pane fade" id="outlook-events">
                            <div class="row">
                                <div class="col-sm-12">
                                    <h2>OUTLOOK CALENDAR: <small>Results from calls to create these meetings in outlook</small></h2>
                                </div>
                                <div class="col-sm-12">
                                    <p>
                                        This is the result of actually making the API calls to add every event here to my outlook calendar.
                                    </p>
                                    <p>
                                        <strong class='label label-danger'>Prevented Email Collision:</strong>
                                    </p>
                                    <br>
                                </div>
                                <br>
                                <div class="col-sm-12">
                                    <?php
                                    if (\Util\session('export_calendar')) {
                                        $create_all_resp = \copro\export_meetings_to_outlook();

                                    }
                                    \Util\session('export_calendar', null);
                                    ?>
                                </div>
                                <br>
                            </div>
                        </div>

                        <?php endif; ?>
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
