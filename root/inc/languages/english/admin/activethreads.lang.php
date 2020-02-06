<?php

$l['act_name'] = 'Active Threads';
$l['act_desc'] = 'Shows the threads that were active (posted to at least once) during a given period.';
$l['act_max_interval_in_mins_title'] = 'Maximum allowed interval (minutes)';
$l['act_max_interval_in_mins_desc' ] = 'Active thread queries over a large date-time interval can be expensive. Set here, in minutes, the maximum permissible activity period that users may enter. For reference: 1,440 minutes = a day; 10,080 minutes = a week; 20,160 minutes = a fortnight. Set to zero for unlimited intervals. Large sites may wish to reduce the default, which is 43,200 minutes = 30 days.';
$l['act_display_thread_avatar_title'] = 'Display thread\'s user avatar?';
$l['act_display_thread_avatar_desc' ] = 'If yes, the avatar of each thread\'s starter will be shown in the listing';
$l['act_display_earliestpost_avatar_title'] = 'Display earliest post\'s user avatar?';
$l['act_display_earliestpost_avatar_desc' ] = 'If yes, the avatar of the user of each thread\'s earliest post will be shown in the listing';
$l['act_display_latestpost_avatar_title'] = 'Display latest post\'s user avatar?';
$l['act_display_latestpost_avatar_desc' ] = 'If yes, the avatar of the user of each thread\'s latest post will be shown in the listing';
$l['act_per_usergroup_permissions_heading'] = 'Active Threads Limits';
$l['act_per_usergroup_title'              ] = 'Enable per-usergroup interval setting overrides';
$l['act_per_usergroup_desc'               ] = 'When enabled, the "Maximum allowed interval (minutes)" setting is taken from the user\'s usergroup rather than from the global setting above. This setting can be customised for each usergroup in the ACP under Users & Groups -> <a href=\"index.php?module=user-groups\">Groups</a> -> [Selected group] -> Users and Permissions [tab] -> Active Threads Limits [heading].';
$l['act_default_days_title'               ] = 'Default days';
$l['act_default_days_desc'                ] = 'The default for the "days" component of the Active Threads interval when no parameters are specified.';
$l['act_default_hours_title'              ] = 'Default hours';
$l['act_default_hours_desc'               ] = 'The default for the "hours" component of the Active Threads interval when no parameters are specified.';
$l['act_default_mins_title'               ] = 'Default minutes';
$l['act_default_mins_desc'                ] = 'The default for the "minutes" component of the Active Threads interval when no parameters are specified.';
$l['act_default_sort_field_title'         ] = 'Default sort field';
$l['act_default_sort_field_desc'          ] = 'The default field to sort by when displaying active threads.';
$l['act_default_sort_field_num_posts'     ] = 'Number of posts';
$l['act_default_sort_field_date_earliest' ] = 'Date of earliest post';
$l['act_default_sort_field_date_latest'   ] = 'Date of latest post';
$l['act_default_sort_field_thread_subject'] = 'Thread subject';
$l['act_default_sort_field_thread_username'] = 'Thread author';
$l['act_default_sort_field_thread_dateline'] = 'Thread start';
$l['act_default_sort_field_forum_name'     ] = 'Containing forum';
$l['act_default_sort_field_min_username'   ] = 'Author of earliest post';
$l['act_default_sort_field_max_username'   ] = 'Author of latest post';
$l['act_default_sort_direction_title'      ] = 'Default sort direction';
$l['act_default_sort_direction_desc'       ] = 'The default direction in which to sort the default sort field when displaying active threads';
$l['act_ascending'                         ] = 'Ascending';
$l['act_descending'                        ] = 'Descending';

