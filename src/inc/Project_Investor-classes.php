<?php
/**
 * Created by michael on 2/22/16.
 */


// This is how project model will look
$project_model = array(
    'project_title' => 'movie_title',
    'director' => array(
        'fname' => 'first_name',
        'lname' => 'last_name',
        'email' => 'email'
    ),
    'producer' => array(
        'fname' => 'first_name',
        'lname' => 'last_name',
        'email' => 'email'
    ),

    'director_first_name' => 'first_name',
    'director_last_name' => 'last_name',
    'director_email' => 'email',
    'producer_first_name' => 'first_name',
    'producer_last_name' => 'last_name',
    'producer_email' => 'email',
    'id' => ':id:',
);

// This is how Investor Model will look
$investor_model = array(
    'investor_name' => 'full_name',
    'investor_first_name' => 'first_name',
    'investor_last_name' => 'last_name',
    'investor_email' => 'email',
    'id' => ':id:',
);



class ListArray {
    public $items = array();

    function __construct() {}

    function pop() {
        return array_pop($this->items);
    }

    function push($item) {
        return $this->items[] = $item;
    }

    function shift() {
        return array_shift($this->items);
    }

    function unshift($item) {
        return array_unshift($this->items, $item);
    }

    function insert_at($index, $value = null) {
        $this->items = array_merge(array_slice($this->items, 0, $index), array($value), array_slice($this->items, $index));
        return $this->items;
    }

    function remove_by($property, $value) {

    }

    function contains($item) {
        return in_array($item, $this->items);
    }

    function has_key($key) {
        return array_key_exists($key, $this->items);
    }

    function length() {
        return count($this->items);
    }

    function merge($array_to_merge) {
        $this->items = array_merge($this->items, $array_to_merge);
        return $this->items;
    }
}



class Investor extends ListArray {
    public $first_name = '';
    public $last_name = '';
    public $name = '';
    public $email;
    public $id;
    public $projects = array();

    function __construct($config) {
        $this->email = $config->investor_email;
        $this->first_name = $config->investor_first_name;
        $this->last_name = $config->investor_last_name;
        $this->name = "{$this->last_name}, {$this->first_name}";
        $this->id = $config->id;
    }


    /**
     * Is Investor Supposed to Meet with Project?
     * @return bool
     */
    public function interested($pid) {
        $pid = (int)$pid;
        return in_array($pid, $this->projects);
    }

    function schedule_meeting($start_time, $project) {
        $project_title = $project->project_title;
        $project_id = $project->id;
        echo "<br>Investor {$this->id}, {$this->name}: Schedule meeting at {$start_time} to view project {$project_id}, {$project_title}.";

    }


    /**
     * Add Project ID to List of Projects Investor Should See.
     *
     * @param $project
     *
     * @return mixed|null
     */
    function add_project($project) {
        if (is_object($project))
            $pid = (int)$project->id;

        elseif (is_array($project) && isset($project['id']))
            $pid = (int)$project['id'];

        else
            $pid = (int)$project;

        // Add project to $this->projects if not already there.
        if (!in_array($pid, $this->projects))
            $this->projects[] = $pid;

        return array_search( $pid, $this->projects );
    }


    public function tooltip() {
        $projects = implode(', ', $this->projects);
        return "<span class='label label-primary' data-container='body' data-toggle='popover' data-placement='top' data-trigger='hover' data-title='Meetings: {$this->name}' data-content='{$projects}'><i class='glyphicon glyphicon-user'></i> {$this->name}</span>";
    }

}


/**
 * Class Project
 *
 * Holds the names and emails of producer/director and also the ids of all investors which want
 * to hold a meeting with the project.
 */
class Project {
    public $project_title;
    public $producer;
    public $director;
    public $emails = array();
    public $contacts = array();
    public $interested = array();
    public $id;


    function __construct($project=null) {
        if ( is_null( $project ) ) {
            $this->id = null;
            $this->title = 'Empty Slot';
            return $this;
        }
        $this->id = (int)$project->id;
        // Those investors interested in project (string -> array)
        $this->interested = !property_exists($project, 'interested') ?
            array() :
            array_filter(explode('~', $project->interested), function ($val) {
            return ! ! $val;
        });
        if ($project->project_title) {
            $this->project_title = $project->project_title;
        }
        $this->director = new \stdClass();
        $this->director->email = $project->director_email;
        $this->director->lname = $project->director_last_name;
        $this->director->fname = $project->director_first_name;

        $this->producer = new \stdClass();
        $this->producer->email = $project->producer_email;
        $this->producer->lname = $project->producer_last_name;
        $this->producer->fname = $project->producer_first_name;


        if ($project->director_email || $project->director_first_name || $project->director_last_name) {
            $this->contacts[] = array(
                'address' => $project->director_email,
                'name' => $project->director_first_name . ' ' . $project->director_last_name
            );

            if ($project->director_email) {
                $this->emails[] = $project->director_email;
            }
        }

        if ($project->producer_email || $project->producer_first_name || $project->producer_last_name) {
            $this->contacts[] = array(
                'address' => $project->producer_email,
                'name' => $project->producer_first_name . ' ' . $project->producer_last_name
            );

            if ($project->producer_email) {
                $this->emails[] = $project->producer_email;
            }
        }

    }


    public function tooltip() {
        if (is_null($this->id)) {
            return "--";
        }
        $tooltip = "Director Email: {$this->director->email}, Producer email: {$this->producer->email}";
        return "<span class='label label-info' data-container='body' data-toggle='popover' data-placement='top' data-trigger='hover' data-title='{$this->project_title}' data-content='{$tooltip}'>{$this->id}</span>";
    }
}