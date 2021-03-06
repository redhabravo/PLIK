<?php 
class Main_m extends CI_Model {

	public function __construct(){
		parent::__construct();
	}

	// public function get_data()
	// {
	// 	$data['pages'] 		= '';
	// 	$data['menus'] 		= $this->get_categories();
	// 	$data['headline'] 	= $this->get_posts(array(), 'Article_DateCreate DESC', 5, 0);
	// 	$data['posts'] 		= $this->get_posts(array(), 'Article_DateCreate DESC', 40, 0);
	// 	return $data;
	// }

	public function get_category()
	{
		$filter = array('category_parent'=>0);
		$this->db->limit(8);
		$category = $this->db->get_where('categories', $filter)->result();
		
		$subs = array();
		foreach ($category as $value) {
			$filter = array('category_parent'=>$value->category_id);
			$subs[$value->category_id] = $this->db->get_where('categories', $filter)->result();
		}

		$data['category'] = $category; 
		$data['subcategory'] = $subs;
		return $data;
	}

	public function get_post($config = array())
	{
		$date 	= date('Y-m-d H:i:s');		
		$filter = array('post_type'=>'post','post_status'=>'publish','post_date <'=>$date);
		$order 	= isset($config['order']) ? $config['order'] : 'a.post_date DESC' ;
		$limit 	= isset($config['limit']) ? $config['limit'] : 10 ;
		$page 	= isset($config['page']) ? $config['page'] : 0 ;

		// pencarian
		if(isset($config['keyword'])){
			$keyword = array('a.post_title LIKE'=>'%'.$config['keyword'].'%');
			$filter = array_merge($keyword, $filter);
		}
		if(isset($config['keyword']) && isset($config['cat'])){
			$keyword = array('a.post_title LIKE'=>'%'.$config['keyword'].'%','b.category_id'=>$config['cat']);
			$filter = array_merge($keyword, $filter);
		}

		// category
		if(isset($config['category'])){
			$category = array('b.category_id'=>$config['category']);
			$filter = array_merge($category, $filter);
		}

		$this->db->select('*');
		$this->db->from('wp_posts a');		
		$this->db->join('wp_posts_categories b','a.ID = b.post_id','left');		
		$this->db->join('categories c','b.category_id = c.category_id','left');		
		$this->db->join('users d','a.post_author = d.id','left');		
		$this->db->where($filter);
		$this->db->group_by('a.ID');
		$this->db->order_by($order);
		$this->db->limit($limit, $page);
		$query = $this->db->get();
		$data['result'] = $query->result();
		
		$this->db->select('a.ID');
		$this->db->join('wp_posts_categories b','a.ID = b.post_id','left');
		// $this->db->join('users c','wp_posts.post_author = c.id','left');		
		$data['count'] = $this->db->get_where('wp_posts a', $filter)->num_rows();

		return $data;
	}

	public function get_post_detail($id = 0)
	{
		$date = date('Y-m-d H:i:s');
		$filter = array('post_type'=>'post','post_status'=>'publish','post_date <'=>$date,'a.ID'=>$id);
		
		$this->db->select('*');
		$this->db->from('wp_posts a');	
		$this->db->join('users b','a.post_author = b.id','left');			
		$this->db->where($filter);
		$this->db->limit(1);
		$query = $this->db->get();
		$data['result'] = $query->row();

		$this->db->select('a.ID');
		$this->db->join('users b','a.post_author = b.id','left');			
		$data['count'] = $this->db->get_where('wp_posts a', $filter)->num_rows();
		return $data;
	}

	public function count($tb = '', $column = '', $filter = array())
	{
		$this->db->select($column);
		return $this->db->get_where($tb, $filter)->num_rows();
	}

	public function get_post_popular()
	{
		$date = date('Y-m-d H:i:s');
		return $this->db->query("SELECT * FROM 
			(SELECT a.*,c.category_name,d.first_name,d.last_name FROM wp_posts a
			LEFT JOIN wp_posts_categories b ON a.ID = b.post_id
			LEFT JOIN categories c ON b.category_id = c.category_id
			LEFT JOIN users d ON a.post_author = d.id
			WHERE post_type = 'post' AND post_status = 'publish' AND post_date <= '$date' 
			GROUP BY ID
			ORDER BY ID DESC
			LIMIT 50) as temp ORDER BY post_view DESC LIMIT 9
			")->result();
	}

	public function get_category_name()
    {
        $list = array();
        $category = $this->db->get_where('categories',array())->result();
        foreach ($category as $value) {
            $list[$value->category_id] = $value->category_name;
        }
        return $list;
    }

	public function post_hit($id = 0)
	{
		$this->db->query("UPDATE wp_posts SET post_view = (post_view + 1) WHERE ID = '$id'");
	}
}