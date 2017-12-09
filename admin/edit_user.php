<?php

define('BASEPATH', 'Staff');
require_once('../applications/wrapper.php');

if (!$LAYER->perm->check('access_administration')) {
    redirect(SITE_URL);
}//Checks if user has permission to create a thread.
//require_once('template/top.php');
echo $ADMIN->template('top');
$notice = '';

function list_groups()
{
    global $MYSQL;
    $query = $MYSQL->query('SELECT * FROM {prefix}usergroups');
    $return = '';
    foreach ($query as $s) {
        $checked = ($s['id'] == $check) ? ' selected' : '';
        $return .= '<option value="' . $s['id'] . '"' . $checked . '>' . $s['group_name'] . '</option>';
    }
    return $return;
}


if ($PGET->g('id')) {

    $id = clean($PGET->g('id'));
    /*$MYSQL->where('id', $id);
    $query = $MYSQL->get('{prefix}forum_node');*/
    $MYSQL->bind('id', $id);
    $query = $MYSQL->query('SELECT * FROM {prefix}users WHERE id = :id');

    if (!empty($query)) {

        if (isset($_POST['update'])) {
            try {
                NoCSRF::check('csrf_token', $_POST);

                $title = clean($_POST['node_title']);
                $desc = (!$_POST['node_desc']) ? '' : clean($_POST['node_desc']);
                $locked = (isset($_POST['lock_node'])) ? '1' : '0';
                $labels = trim($_POST['labels']);
                $labels = explode(PHP_EOL, $labels);
                $all_u = (isset($_POST['allowed_ug'])) ? implode(',', $_POST['allowed_ug']) : '0';

                if (!$title) {
                    throw new Exception ('All fields are required!');
                } else {
                    if (substr_count($_POST['node_parent'], '&') > 0) {
                        $explode = explode('&', $_POST['node_parent']);
                        $parent = node($explode['1']);
                        $in_category = $parent['in_category'];
                        $node_type = 2;
                        $parent_node = $parent['id'];
                    } else {
                        $in_category = clean($_POST['node_parent']);
                        $node_type = 1;
                        $parent_node = 0;
                    }
                    $MYSQL->bind('node_name', $title);
                    $MYSQL->bind('name_friendly', title_friendly($title));
                    $MYSQL->bind('node_desc', $desc);
                    $MYSQL->bind('node_locked', $locked);
                    $MYSQL->bind('in_category', $in_category);
                    $MYSQL->bind('node_type', $node_type);
                    $MYSQL->bind('parent_node', $parent_node);
                    $MYSQL->bind('allowed_usergroups', $all_u);
                    $MYSQL->bind('id', $id);
                    try {
                        $u_query = $MYSQL->query('UPDATE {prefix}forum_node SET node_name = :node_name,
                                                                           name_friendly = :name_friendly,
                                                                           node_desc = :node_desc,
                                                                           node_locked = :node_locked,
                                                                           in_category = :in_category,
                                                                           node_type = :node_type,
                                                                           parent_node = :parent_node,
                                                                           allowed_usergroups = :allowed_usergroups
                                                                           WHERE id = :id');
                        $MYSQL->bind('node_id', $id);
                        $MYSQL->query("DELETE FROM {prefix}labels WHERE node_id = :node_id");
                        if (!empty($labels) && $labels[0] != "") {
                            foreach ($labels as $label) {
                                $MYSQL->bind('node_id', $id);
                                $MYSQL->bind('label', $label);
                                $MYSQL->query("INSERT INTO {prefix}labels (node_id, label) VALUES (:node_id, :label)");
                            }
                        }
                        redirect(SITE_URL . '/admin/manage_node.php/notice/edit_success');
                    } catch (mysqli_sql_exception $e) {
                        throw new Exception ('Error updating node.');
                    }

                }

            } catch (Exception $e) {
                $notice .= $ADMIN->alert(
                    $e->getMessage(),
                    'danger'
                );
            }
        }

        $token = NoCSRF::generate('csrf_token');
        echo $ADMIN->box(
            'Edit User (' . $query['0']['username'] . ') <p class="pull-right"><a href="' . SITE_URL . '/admin/members.php" class="btn btn-default btn-xs">Back</a></p>',
            $notice .
            '<form action="" method="POST">
                <input type="hidden" name="csrf_token" value="' . $token . '">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" value="' . $query['0']['username'] . '" class="form-control" />
                <label for="email">Email Address</label>
                <input type="text" name="email" id="email" value="' . $query['0']['user_email'] . '" class="form-control" />
                <label for="usermsg">User Message</label>
                <input type="text" name="usermsg" id="usermsg" value="' . $query['0']['user_message'] . '" class="form-control" />
                <label for="location">Location</label>
                <input type="text" name="location" id="location" value="' . $query['0']['location'] . '" class="form-control" />
                <label for="usergroup">Usergroup</label><br />
                <select name="usergroup" id="usergroup" style="width:100%;">
                <option value="' . $query['0']['user_group'] . '" selected>Dont Change</option>
                ' . list_groups() . '
                </select><br /><br />
                <input type="submit" name="update" value="Save Changes" class="btn btn-default" />
            </form>',
            '',
            '12'
        );

    } else {
        redirect(SITE_URL . '/admin');
    }

} else {
    redirect(SITE_URL . '/admin');
}

//require_once('template/bot.php');
echo $ADMIN->template('bot');
?>
