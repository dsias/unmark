<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Marks extends Plain_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->redirectIfLoggedOut();
        $this->load->model('users_to_marks_model', 'user_marks');
    }

     /*
        - Add a mark
        - URLS
            /mark/add
            /api/mark/add

        // Query variables
         - title : Required
         - url   : Required
         -
    */
    public function add()
    {
        $redirect  = null;
        $view      = null;
        $url       = (isset($this->db_clean->url)) ? $this->db_clean->url : null;
        $title     = (isset($this->db_clean->title)) ? $this->db_clean->title : null;
        $user_mark = parent::addMark(array('url' => $url, 'title' => $title));

        // Set some info
        if (! isset($user_mark->mark_id)) {
            $this->data['error'] = $user_mark;
            $view = 'marks/add';
        }
        else {
            $this->data['mark'] = $user_mark;
            $redirect           = '/mark/info/' . $user_mark->mark_id . '?bookmarklet=true';
        }

        // Figure what to do here (api, redirect or generate view)
        $this->figureView($view, $redirect);
    }

    // Archive a mark
    // both api and web view
    public function archive($mark_id=0)
    {
        // Only allow ajax and API
        parent::redirectIfWebView();

        // Figure correct way to handle if no mark id
        if (empty($mark_id) || ! is_numeric($mark_id)) {
            header('Location: /');
            exit;
        }

        // Check for CSRF
        if ($this->csrf_valid === true) {
            // Update
            $mark = $this->user_marks->update("users_to_marks.user_id = '" . $this->user_id . "' AND users_to_marks.users_to_mark_id = '" . $mark_id . "'", array('archived_on' => date('Y-m-d H:i:s')));

            if ($mark === false) {
                $this->data['errors'] = formatErrors(1);
            }
            else {
                $this->data['mark'] = $mark;
            }
        }

        // Figure view
        $this->figureView('marks/archive');
    }

    public function delete($mark_id=0)
    {
         parent::redirectIfWebView();

        // Figure correct way to handle if no mark id
        if (empty($mark_id) || ! is_numeric($mark_id)) {
            header('Location: /');
            exit;
        }

        // Set where
        $where = "users_to_marks.user_id = '" . $this->user_id . "' AND users_to_marks.users_to_mark_id = '" . $mark_id . "'";

        // Check if mark exists for this user
        $total = $this->user_marks->count($where);

        if ($total < 1) {
            $this->data['errors'] = formatErrors(4);
        }
        else {
            $mark = $this->user_marks->delete($where);
            if (! isset($mark->active)) {
                $this->data['errors'] = formatErrors(601);
            }
            elseif ($mark->active != '0') {
                $this->data['errors'] = formatErrors(7);
            }
            else {
                $this->data['mark'] = $mark;
            }
        }

        $this->figureView();
    }

    // Edit a mark
    // Both API and web view
    public function edit($mark_id=0)
    {

        parent::redirectIfWebView();

        // Figure correct way to handle if no mark id
        if (empty($mark_id) || ! is_numeric($mark_id)) {
            header('Location: /');
            exit;
        }

        // Check for CSRF
        if ($this->csrf_valid === true) {

            // Figure what options to send for update
            $options = array();

            // If label ID is found, attach it
            if (isset($this->clean->label_id) && is_numeric($this->label_id)) {
                $options['label_id'] = $this->clean->label_id;
            }

            // If notes are present set them
            if (isset($this->db_clean->notes)) {
                $options['notes'] = $this->db_clean->notes;

                // Check for hashmarks to tags
                $tags = getTagsFromHash($options['notes']);
            }

            // If tags are present, handle differentlu
            // Need to add to tags table first
            // Then create association
            // If notes are present set them
            if (isset($tags)) {
                // Update users_to_marks record
                $this->load->model('tags_model', 'tag');
                $this->load->model('user_marks_to_tags_model', 'mark_to_tag');

                $tag_ids = array();
                foreach ($tags as $k => $tag) {
                    $tag_name  = trim($tag);
                    $slug      = generateSlug($tag);

                    if (! empty($slug)) {
                        $tag = $this->tag->read("slug = '" . $slug . "'", 1, 1, 'tag_id');
                        if (! isset($tag->tag_id)) {
                            $tag = $this->tag->create(array('name' => $tag_name, 'slug' => $slug));
                        }

                        // Add tag to mark
                        if (isset($tag->tag_id)) {
                            $res = $this->mark_to_tag->create(array('users_to_mark_id' => $mark_id, 'tag_id' => $tag->tag_id, 'user_id' => $this->user_id));
                        }

                        // Save all tag ids
                        if (isset($res->tag_id)) {
                            array_push($tag_ids, $res->tag_id);
                        }
                    }
                }

                // Delete old tags
                $delete_where = (! empty($tag_ids)) ? " AND tag_id <> '" . implode("' AND tag_id <> '", $tag_ids) . "'" : '';
                $delete       = $this->mark_to_tag->delete("users_to_mark_id = '" . $mark_id . "' AND user_id = '" . $this->user_id . "'" . $delete_where);
            }


            // Update users_to_marks record
            $mark = $this->user_marks->update("users_to_marks.user_id = '" . $this->user_id . "' AND users_to_marks.users_to_mark_id = '" . $mark_id . "'", $options);

            // Check if it was updated
            if ($mark === false) {
                $this->data['errors'] = formatErrors(3);
            }
            else {
                $this->data['mark'] = $mark;

                // Check if label id was set
                // if so get the parent mark id
                // Then add a smart label for this domain
                if (isset($options['label_id']) && ! empty($options['label_id'])) {
                    $this->load->model('labels_model', 'labels');
                    $smart_info = getSmartLabelInfo($mark->url);
                    $total      = $this->labels->count("labels.user_id = '" . $this->user_id . "' AND labels.smart_key = '" . $smart_info['key'] . "'");

                    // If not found, create it with label
                    // Else update current
                    if ($total < 1 && $options['label_id'] != '1') {
                        $label = $this->labels->create(array(
                            'smart_label_id' => $options['label_id'],
                            'domain'         => $smart_info['domain'],
                            'smart_key'      => $smart_info['key'],
                            'user_id'        => $this->user_id
                        ));
                    }
                    else {
                        $active = ($options['label_id'] == '1') ? '0' : '1';
                        $label = $this->labels->update("labels.user_id = '" . $this->user_id . "' AND labels.smart_key = '" . $smart_info['key'] . "'", array(
                            'smart_label_id' => $options['label_id'],
                            'active'         => $active
                        ));
                    }
                }
            }
        }
        else {
            $this->data['errors'] = formatErrors(600);
        }

        // Figure what to do here (api, redirect or generate view)
        $this->renderJSON();

    }

    public function get($what='stats')
    {
        parent::redirectIfWebView();
        $method = 'get' . ucwords($what);
        if (method_exists($this, $method)) {
            $total = $this->$method();
            parent::renderJSON();
        }
        else {
            header('Location: /');
            exit;
        }

    }

    private function getLabels()
    {
        $this->load->model('labels_model', 'labels');
        $this->data['labels'] = $this->labels->getSystemLabels();

        if ($this->data['labels'] !== false) {
            $this->load->model('labels_model', 'labels');
            foreach($this->data['labels'] as $k => $label) {
                $this->data['labels'][$k]->total_marks = $this->user_marks->count("label_id = '" . $label->label_id . "' AND user_id = '" . $this->user_id . "'");
            }
        }

    }


    private function getStats()
    {
        $this->data['stats'] = array();

        // Get total marks saved over the last 5 days
        $this->data['stats']['saved'] = array(
            'today'      => self::totalSaved('today'),
            'yesterday'  => self::totalSaved('yesterday'),
            '2 days ago' => self::totalSaved('-2 days'),
            '3 days ago' => self::totalSaved('-3 days'),
            '4 days ago' => self::totalSaved('-4 days'),
            'total'      => self::totalSaved()
        );

        // Get the total marks archived over the last 5 days
        $this->data['stats']['archived'] = array(
            'today'      => self::totalArchived('today'),
            'yesterday'  => self::totalArchived('yesterday'),
            '2 days ago' => self::totalArchived('-2 days'),
            '3 days ago' => self::totalArchived('-3 days'),
            '4 days ago' => self::totalArchived('-4 days'),
            'total'      => self::totalArchived()
        );

        // Get total marks for a series of ranges
        $this->data['stats']['marks'] = array(
            'today'         => self::totalMarks('today'),
            'yesterday'     => self::totalMarks('yesterday'),
            'last week'     => self::totalMarks('-7 days', 'today'),
            'last month'    => self::totalMarks('-1 month', 'today'),
            'last 3 months' => self::totalMarks('-3 months', 'today'),
            'last 6 months' => self::totalMarks('-6 months', 'today'),
            'last year'     => self::totalMarks('-1 year', 'today'),
            'ages ago'      => self::totalMarks('-20 years', '-1 year'),
            'total'         => self::totalMarks()
        );

    }

    // Get the 10 most used tags for a user
    private function getTags()
    {
        $this->data['tags'] = array();
        $this->load->model('user_marks_to_tags_model', 'user_tags');
        $this->data['tags']['popular'] = $this->user_tags->getPopular($this->user_id);
        $this->data['tags']['recent']  = $this->user_tags->getMostRecent($this->user_id);
    }

    // The index of the marks page
    public function index()
    {

        /*
        - Figure the state of things by URL
            URLS:
                /marks
                /marks/VALID_LOOKUP_KEY
                /marks/DATE(/DATE)?
                /marks/archive
                /marks/search*?q=QUERY
                /marks/tag*
                /marks/label*
        */

        // Get URI segments
        $segments = $this->uri->segment_array();
        $lookup   = (isset($segments[2]) && ! empty($segments[2])) ? strtolower(trim(urldecode($segments[2]))) : 'all';
        $finish   = (isset($segments[3]) && ! empty($segments[3])) ? strtolower(trim(urldecode($segments[3]))) : null;

        // Set allowable textual starts
        $valid_lookups = array(
            'all'               => array('start' => null, 'finish' => null),
            'archive'           => array('start' => null, 'finish' => null),
            'search'            => array('start' => null, 'finish' => null),
            'today'             => array('start' => strtotime('today'), 'finish' => strtotime('today')),
            'yesterday'         => array('start' => strtotime('yesterday'), 'finish' => strtotime('yesterday')),
            'last-week'         => array('start' => strtotime('-1 week'), 'finish' => strtotime('today')),
            'last-month'        => array('start' => strtotime('-1 month'), 'finish' => strtotime('today')),
            'last-three-months' => array('start' => strtotime('-3 months'), 'finish' => strtotime('today')),
            'last-six-months'   => array('start' => strtotime('-6 months'), 'finish' => strtotime('today')),
            'last-year'         => array('start' => strtotime('-1 year'), 'finish' => strtotime('today')),
            'ages-ago'          => array('start' => strtotime('-20 years'), 'finish' => strtotime('-1 year'))
        );

        // If $lookup is one of the following, search by time is disabled
        $no_time = array('all', 'archive', 'search');

        $options = array();

        // Figure when
        $where_time = null;
        if (array_key_exists($lookup, $valid_lookups)) {
            $where_time .= (! in_array($lookup, $no_time)) ? " AND UNIX_TIMESTAMP(users_to_marks.created_on) >= '" . $valid_lookups[$lookup]['start'] . "'" : '';
            $where_time .= (! in_array($lookup, $no_time)) ? " AND UNIX_TIMESTAMP(users_to_marks.created_on) <= '" . $valid_lookups[$lookup]['finish'] . "'" : '';
            $this->data['lookup_type'] = $lookup;
        }

        // Label Lookups
        // Get label ID if need be
        // Set where and lookup type
        elseif ($lookup == 'label') {

            // Get label ID
            $label_id = $finish;
            if (! is_numeric($label_id)) {
                $this->load->model('labels_model', 'label');
                $label      = $this->label->read("slug = '" . $this->db->escape_str($label_id) . "'", 1, 1, 'label_id, name');
                $label_id   = (isset($label->label_id)) ? $label->label_id : 0;
                $label_name = (isset($label->name)) ? $label->name : 0;
            }

            // Set the new where clause
            // Set lookup type
            $where_time                = " AND users_to_marks.label_id = '" . $label_id . "'";
            $this->data['lookup_type'] = 'label';
        }

        // Tag lookups
        // Get tag id if need be
        // Set lookup type
        // Set options to pass to readComplete
        // readComplete will see that tag_id was sent and add an additional INNER JOIN
        elseif ($lookup == 'tag') {
            // Get label ID
            $tag_id = $finish;
            if (! is_numeric($tag_id)) {
                $this->load->model('tags_model', 'tag');
                $tag      = $this->tag->read("slug = '" . $this->db->escape_str($tag_id) . "'", 1, 1, '*');
                $tag_id   = (isset($tag->tag_id)) ? $tag->tag_id : 0;
                $tag_slug = (isset($tag->slug)) ? $tag->slug : null;
            }

            // Set the new where clause
            // Set lookup type
            $this->data['lookup_type'] = 'tag';
            $options['tag_id'] = $tag_id;
        }

        // Date Range Search
        else {
            // Check for valid dates
            $dates       = findStartFinish($lookup, $finish);
            $where_time .= " AND UNIX_TIMESTAMP(users_to_marks.created_on) >= '" . $dates['start'] . "'";
            $where_time .= " AND UNIX_TIMESTAMP(users_to_marks.created_on) <= '" . $dates['finish'] . "'";
            $this->data['lookup_type'] = 'custom_date';
        }

        // Figure the page number
        $page = findPage();

        // Archives
        $archive = ($lookup == 'archive') ? 'IS NOT NULL' : 'IS NULL';

        // Search it up
        $search = null;
        if (isset($this->db_clean->q) && ! empty($this->db_clean->q)) {
            $search = " AND users_to_marks.notes LIKE '%" . $this->db_clean->q . "%'";
            $options['search']  = $this->db_clean->q;
            $options['user_id'] = $this->user_id;
        }

        // Set where
        $where = "users_to_marks.user_id='". $this->user_id . "' AND users_to_marks.active = '1' AND users_to_marks.archived_on " . $archive . $where_time . $search;

        // Get all the marks
        $marks = $this->user_marks->readComplete($where, $this->limit, $page, null, $options);

        // Check for marks
        // If false, return error; set total to 0
        if ($marks === false) {
            $this->data['errors'] = formatErrors(2);
            $this->data['total']  = 0;
        }
        // If not false
        // Set the marks
        // Check for a JOIN to send to the getTotals call
        // Get the totals
        else {

            // Set marks
            $this->data['marks'] = $marks;

            // If a search, get totals here
            if (isset($options['search'])) {
                $this->data = $this->user_marks->getTotalsSearch($page, $this->limit, $this->data, $options['search'], $options['user_id']);
            }
            // Everthing else here
            else {
                $join       = (isset($options['tag_id']) && ! empty($options['tag_id'])) ? "INNER JOIN user_marks_to_tags UMTT ON users_to_marks.users_to_mark_id = UMTT.users_to_mark_id AND UMTT.tag_id = '" . $options['tag_id'] . "'" : null;
                $this->data = $this->user_marks->getTotals($where, $page, $this->limit, $this->data, $join);
            }
        }

        // If web view
        // Get stats, labels and tags
        // else skip this section and just return the marks
        if (parent::isWebView() === true) {
            self::getStats();
            self::getLabels();
            self::getTags();

            // If looking up by label, set the current label
            if ($lookup == 'label') {
                foreach ($this->data['labels'] as $k => $label) {
                    $this->data['labels'][$k]->current = ($label->label_id == $label_id) ? true : false;

                    // Give Tim Tim his Active Label Array already!
                    if ($this->data['labels'][$k]->current === true) {
                        $this->data['active_label'] = array('label_id' => $label->label_id, 'label_name' => $label->name);
                    }
                }

            }

            // If looking up by tag, set the current tag if applicable
            if ($lookup == 'tag') {
                $tag_keys = array('popular', 'recent');
                foreach ($tag_keys as $key) {
                    foreach ($this->data['tags'][$key] as $k => $tag) {
                        $this->data['tags'][$key][$k]->current = ($tag->tag_id == $tag_id) ? '1' : '0';

                        // Create a Active Tag Array
                        if ($this->data['tags'][$key][$k]->current == '1') {
                            $this->data['active_tag'] = array('tag_id' => $tag->tag_id, 'tag_name' => $tag->name);
                        }
                    }
                }
            }
        }

        // Figure if web, redirect, internal ajax call or API
        $this->figureView('marks/index');
    }

    // Mark detail view
    // Both API and web view
    public function info($mark_id=0)
    {
        // Only allow ajax and API
        //parent::redirectIfWebView();

        // Figure correct way to handle if no mark id
        if (empty($mark_id) || ! is_numeric($mark_id)) {
            header('Location: /');
            exit;
        }

        // Load correct model
        $mark = $this->user_marks->readComplete("users_to_marks.user_id = '" . $this->user_id . "' AND users_to_marks.users_to_mark_id = '" . $mark_id . "' AND users_to_marks.active = '1'", 1);

        // Check for mark
        if ($mark === false) {
            $this->data['errors'] = formatErrors(4);
        }
        else {
            $this->data['mark'] = $mark;
        }

        $this->data['no_header'] = true;
        $this->data['no_footer'] = true;

        // Figure view
        $this->figureView('marks/info');
    }

    public function random()
    {
        // Only allow ajax and API
        parent::redirectIfWebView();

        $this->user_marks->sort = 'RAND()';
        $mark = $this->user_marks->readComplete("users_to_marks.user_id = '" . $this->user_id . "' AND archived_on IS NULL AND users_to_marks.active = '1'", 1);

        // Check for mark
        if ($mark === false) {
            $this->data['errors'] = formatErrors(2);
        }
        else {
            $this->data['mark'] = $mark;
        }

        // Figure view
        $this->figureView();
    }

    // Restore a bookmark from archived
    // Both API and webview
    public function restore($mark_id=0)
    {

        // Only allow ajax and API
        parent::redirectIfWebView();

        // Figure correct way to handle if no mark id
        if (empty($mark_id) || ! is_numeric($mark_id)) {
            header('Location: /');
            exit;
        }

        // Check for CSRF
        if ($this->csrf_valid === true) {
            // Load correct model
            $mark = $this->user_marks->update("users_to_marks.user_id = '" . $this->user_id . "' AND users_to_marks.users_to_mark_id = '" . $mark_id . "'  AND users_to_marks.active = '1'", array('archived_on' => NULL));

            // Check if it was updated
            if ($mark === false) {
                $this->data['errors'] = formatErrors(5);
            }
            else {
                $this->data['mark'] = $mark;
            }
        }

        // Figure view
        $this->figureView('marks/restore');
    }

    public function total($what='marks', $start=null, $finish=null)
    {
        parent::redirectIfWebView();
        $method = 'total' . ucwords($what);
        if (method_exists($this, $method)) {
            $start  = (empty($start)) ? 'today' : strtolower($start);
            $finish = (empty($finish)) ? 'today' : strtolower($finish);
            $this->data['total'] = $this->$method($start, $finish);
            parent::renderJSON();
        }
        else {
            header('Location: /');
            exit;
        }
    }

    private function totalArchived($start=null, $finish=null)
    {
        return $this->user_marks->getTotal('archived', $this->user_id, $start, $finish);
    }

    private function totalMarks($start=null, $finish=null)
    {
        return $this->user_marks->getTotal('marks', $this->user_id, $start, $finish);
    }

    private function totalSaved($start=null, $finish=null)
    {
        return $this->user_marks->getTotal('saved', $this->user_id, $start, $finish);
    }

}