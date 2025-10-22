<?php
class Paging
{
	var $tot_rec;			// Total records in DB  
	var $rec_per_page;		// Records per page to display
	var $tot_pages;			// Total Pages
	var $html_code;			// Html Code for display
	var $url;
	var $offset;
	var $ptype;

	function __construct($url, $tot_rec, $rec_per_page, $nav_length = 10, $offset = 'offset', $ptype = "short")
	{
		$this->tot_rec = $tot_rec;
		$this->rec_per_page = $rec_per_page;
		$this->url = preg_replace('/&?'.$offset.'=[^&]*/', '', $url);
		$this->offset = $offset;
		$this->nav_length = $nav_length;
		$this->ptype = $ptype;
		$this->tot_pages = ceil($this->tot_rec / $this->rec_per_page);
		$this->html_code = array();
	}

	function GetPaging($curr_page)
	{
		if ($this->tot_pages <= 1) {
			return "";
		}
		$page_from = ($curr_page / $this->rec_per_page + 1);
		$page_to   = $page_from + $this->nav_length;
		if ($page_to > $this->tot_pages) {
			$page_from = $this->tot_pages - $this->nav_length;
			$page_to   = $this->tot_pages + 1;
		}
		if ($this->tot_pages < $this->nav_length) {
			$page_from = 1;
			$page_to   = $this->tot_pages + 1;
		}

		if ($curr_page == 0) {
			if ($this->ptype != "short")
				$this->html_code[] = '<a class="no-first"></a>';
			$this->html_code[] = '<a class="no-prev"></a>';
		} else {
			if ($this->ptype != "short")
				$this->html_code[] = '<a class="first" href="' . $this->url . '&' . $this->offset . '=0' . '"></a>';
			$this->html_code[] = '<a class="prev" href="' . $this->url . '&' . $this->offset . '=' . ($curr_page - $this->rec_per_page) . '"></a>';
		}

		if ($this->ptype != "short") {
			for ($i = $page_from; $i < $page_to; $i++) {
				if ($i == ($curr_page / $this->rec_per_page) + 1) {
					$this->html_code[] = "<a href=\"\" class=\"active\">$i</a>";
				} else {
					$this->html_code[] = "<a href=\"$this->url&$this->offset=" . ($i - 1) * $this->rec_per_page . "\">$i</a>";
				}
			}
		}

		if ($curr_page == ($this->tot_pages - 1) * $this->rec_per_page) {
			$this->html_code[] = '<a class="no-next"></a>';
			if ($this->ptype != "short")
				$this->html_code[] = '<a class="no-last"></a>';
		} else {
			$this->html_code[] = '<a class="next" href="' . $this->url . '&' . $this->offset . '=' . ($curr_page + $this->rec_per_page) . '"></a>';
			if ($this->ptype != "short")
				$this->html_code[] = '<a class="last" href="' . $this->url . '&' . $this->offset . '=' . (($this->tot_pages - 1) * $this->rec_per_page) . '"></a>';
		}

		$this->html_code = implode("", $this->html_code);
		$this->fromto = "<div>" . ($curr_page + 1) . " &ndash; " . ($curr_page + $this->rec_per_page) . " of " . $this->tot_rec . "</div>";
		return $this->fromto . $this->html_code;
	}

	function GetPagingAjax($curr_page)
	{
		if ($this->tot_pages <= 1) {
			return "";
		}
		$page_from = ($curr_page / $this->rec_per_page + 1);
		$page_to   = $page_from + $this->nav_length;
		if ($page_to > $this->tot_pages) {
			$page_from = $this->tot_pages - $this->nav_length;
			$page_to   = $this->tot_pages + 1;
		}
		if ($this->tot_pages < $this->nav_length) {
			$page_from = 1;
			$page_to   = $this->tot_pages + 1;
		}
		if ($curr_page == 0) {
			if ($this->ptype != "short")
				//				$this->html_code[] = '<a class="no-first">First</a>';
				$this->html_code[] = '<a class="no-prev">Previous</a>';
		} else {
			//$offUrlPrev = $this->url.'&'.$this->offset.'='.($curr_page - $this->rec_per_page);
			$offUrlPrev = ($curr_page - $this->rec_per_page);
			if ($this->ptype != "short") {
				//$offUrlFirst = $this->url.'&'.$this->offset.'=0';
				$offUrlFirst = 0;
				$this->html_code[] = ''; // '<a class="first" rel="'.$offUrlFirst .'" href="javascript:void(0);"></a>';
			}
			$this->html_code[] = '<a class="no-prev" rel="' . $offUrlPrev . '" href="javascript:void(0);">Previous</a>';
		}
		if ($this->ptype != "short") {
			for ($i = $page_from; $i < $page_to; $i++) {
				if ($i == ($curr_page / $this->rec_per_page) + 1) {
					$this->html_code[] = "<a href=\"javascript:void(0);\" class=\"active\">$i</a>";
				} else {
					//$longUrl = $this->url.'&'.$this->offset.'='.($i-1)*$this->rec_per_page;
					$longUrl = ($i - 1) * $this->rec_per_page;
					$this->html_code[] = '<a href="javascript:void(0);" rel="' . $longUrl . '">' . $i . '</a>';
				}
			}
		}
		if ($curr_page == ($this->tot_pages - 1) * $this->rec_per_page) {
			$this->html_code[] = '<a class="no-next">Next</a>';
			if ($this->ptype != "short")
				$this->html_code[] = ''; //'<a class="no-last"></a>';
		} else {
			//$offUrlNext = $this->url.'&'.$this->offset.'='.($curr_page + $this->rec_per_page);
			$offUrlNext = ($curr_page + $this->rec_per_page);
			$this->html_code[] = '<a class="no-next" rel="' . $offUrlNext . '" href="javascript:void(0);">Next</a>';
			if ($this->ptype != "short") {
				//$offUrlLast = $this->url.'&'.$this->offset.'='.(($this->tot_pages-1)*$this->rec_per_page);
				$offUrlLast = (($this->tot_pages - 1) * $this->rec_per_page);
				//$this->html_code[] = '<a class="last" rel="'.$offUrlLast .'" href="javascript:void(0);"></a>';
			}
		}

		$this->html_code = implode("", $this->html_code);
		//$this->fromto = "<div>".($curr_page+1)." &ndash; ".($curr_page + $this->rec_per_page)." of ".$this->tot_rec."</div>";	
		$this->fromto = '';
		return $this->fromto . $this->html_code;
	}
}
