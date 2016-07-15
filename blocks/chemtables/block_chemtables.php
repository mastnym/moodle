<?php
class block_chemtables extends block_base 
{
	public function init() 
	{
		$this->title = get_string('chemtables', 'block_chemtables');
	}
	public function get_content() 
	{
    global $CFG;
		if ($this->content !== null) 
		{
			return $this->content;
		}
		
		$this->content = new stdClass;
    $this->content->text = "<table>";
    $this->content->text .= "<tr><td><img src='$CFG->wwwroot/blocks/chemtables/pix/atom.png' /></td><td><a href='' onclick=\"window.open('$CFG->wwwroot/blocks/chemtables/tab_rel_atom_hmot.html', 'newwindow', 'width=500, height=500, scrollbars=1'); return false;\">Relativní atomové hmotnosti prvků</a></td></tr>";
    $this->content->text .= "<tr><td><img src='$CFG->wwwroot/blocks/chemtables/pix/zkum.png' /></td><td><a href='' onclick=\"window.open('$CFG->wwwroot/blocks/chemtables/tab_rozpust_latek.html', 'newwindow', 'width=500, height=500, scrollbars=1'); return false;\">Rozpustnosti látek ve vodě za různých teplot</a></td></tr>";
    $this->content->text .= "<tr><td><img src='$CFG->wwwroot/blocks/chemtables/pix/rozp.png' /></td><td><a href='' onclick=\"window.open('$CFG->wwwroot/blocks/chemtables/tab_soucin_rozpust.html', 'newwindow', 'width=500, height=500, scrollbars=1'); return false;\">Součiny rozpustnosti anorganických sloučenin při 25 °C</a></td></tr>";
    $this->content->text .= "<tr><td><img src='$CFG->wwwroot/blocks/chemtables/pix/hust.png' /></td><td><a href='' onclick=\"window.open('$CFG->wwwroot/blocks/chemtables/tab_hustoty_roztoku.html', 'newwindow', 'width=500, height=500, scrollbars=1'); return false;\">Hustoty roztoků kyselin a zásad při 20 °C</a></td></tr>";
    $this->content->text .= "</table>";
		
		return $this->content;
	}
}
?>