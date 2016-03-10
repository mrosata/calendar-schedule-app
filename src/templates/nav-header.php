<?php
/**
 * Created by michael on 3/4/16.
 */

?>
<div class="container-fluid">
    <header class="nav nav-header nav-default">
        <div class="col-sm-8 col-md-10">
            <h2>Meeting Sort Algorithm</h2>
        </div>
        <div class="col-sm-4 col-md-2">
            <?php if ( ! (defined('LOGGED_IN') && LOGGED_IN) ) { ?>
                 <a href="<?= \ms365\oAuthService::getLoginUrl($redirectUri)?>">sign in</a> <?php
            } else {  ?>
                <form method="post" action="index.php">
                    <input name="unset-session-creds" value="1" type="hidden">
                    <button type="submit" class="btn btn-danger">Reset the Session</button>
                </form>
                <?php
            }
            ?>
        </div>
    </header>
</div>
<?php


