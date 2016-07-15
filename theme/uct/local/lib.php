<?php

function get_faculty_name_for_css() {
    global $DB, $PAGE;
    $category_obj = $PAGE->category;
    $catname = "";
    if (isset($category_obj)) {
        $path_chunks = explode("/", $category_obj->path);
        if (count($path_chunks) > 0) {
            $cat = $DB->get_record("course_categories", array("id" => $path_chunks[1]
            ));
            if (isset($cat)) {
                $catname = $cat->name;
            }
        }
    }
    return $catname;
}