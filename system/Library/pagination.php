<?php

class LibraryPagination extends Controller {

	public function paginate(array $setting) {

		if (isset($setting['total'])) {
			$total = $setting['total'];
		} else {
			$total = 0;
		}

		if (isset($setting['page']) && $setting['page'] > 0) {
			$page = (int)$setting['page'];
		} else {
			$page = 1;
		}

		if (isset($setting['limit']) && (int)$setting['limit']) {
			$limit = (int)$setting['limit'];
		} else {
			$limit = 10;
		}

		$num_links = 4;
		$num_pages = ceil($total / $limit);

		if ($page > 1 && $num_pages < $page) {
			$back = true;
		} else {
			$back = false;
		}

		$data['page'] = $page;

		if ($page > 1) {

			$data['first'] = 1;

			if ($page - 1 === 1) {
				$data['prev'] = 1;
			} else {
				$data['prev'] = $page - 1;
			}

		} else {
			$data['first'] = '';
			$data['prev'] = '';
		}

		$data['links'] = [];

		if ($num_pages > 1) {
			if ($num_pages <= $num_links) {
				$start = 1;
				$end = $num_pages;
			} else {
				$start = $page - floor($num_links / 2);
				$end = $page + floor($num_links / 2);

				if ($start < 1) {
					$end += abs($start) + 1;
					$start = 1;
				}

				if ($end > $num_pages) {
					$start -= ($end - $num_pages);
					$end = $num_pages;
				}
			}

			for ($i = $start; $i <= $end; $i++) {
				$data['links'][] = $i;
			}
		}

		if ($num_pages > $page) {
			$data['next'] = $page + 1;
			$data['last'] = $num_pages;
		} else {
			$data['next'] = '';
			$data['last'] = '';
		}

		if ($num_pages > 1 || $back) {
			return $data;
		} else {
			return '';
		}
		
	}

}

