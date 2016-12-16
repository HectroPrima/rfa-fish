<?php

add_filter('body_class', 'fix_body_class_for_sidebar', 20, 2);
function fix_body_class_for_sidebar($wp_classes, $extra_classes) {
    if( is_single() || is_page() ){ 
       if (in_array('singular',$wp_classes)){
            foreach($wp_classes as $key => $value) {
                if ($value == 'singular') 
                    unset($wp_classes[$key]);
            }
        }
    }
    return array_merge($wp_classes, (array) $extra_classes);
}

function get_mytags_desc() {
    $tags = get_tags_id_array_from_query();
    $args = array('post_type' => 'mytags','tag__and' => implode($tags,','));
    $custom_query = new WP_Query($args);
    while($custom_query->have_posts()) : $custom_query->the_post();
        $tags_ = get_tags_id_array($post->ID);
        $dif_tags = array_diff( $tags_, $tags );
        if ( count($dif_tags) == 0 ) {
            wp_reset_postdata();
            $custom_query->post->post_title = do_shortcode($custom_query->post->post_title);
            $custom_query->post->post_content = apply_filters('the_content',$custom_query->post->post_content);
            return $custom_query->post;
        }
    endwhile;
    wp_reset_postdata();
}

function get_mytags_link() {
    if (get_site_url()) {
        $tags = get_tags_slug_array($post->ID);
        if ($tags) {
            echo ' <a href="'.get_site_url().'/tag/'.implode($tags,'+').'">@</a>';
        }
    }
}

function get_tags_id_array_from_query() {
    $tag_ids = array();
    $slugs = get_query_var('tag_slug__and');
    if (count($slugs)) {
        $tags = get_terms(array('taxonomy'=>'post_tag', 'slug'=>$slugs));
        foreach( $tags as $tag ) {
            $tag_ids[] = $tag->term_id;
        }
    }
    return $tag_ids;
}

function get_tags_id_array( $postID ) {
    $tag_ids = array();
    $tags = get_the_tags($postID);
    foreach( $tags as $tag ) {
        $tag_ids[] = $tag->term_id;
    }
    return $tag_ids;
}

function get_tags_slug_array( $postID ) {
    $tag_ids = array();
    $tags = get_the_tags($postID);
    if ($tags) {
        foreach( $tags as $tag ) {
            $tag_ids[] = $tag->slug;
        }
    }
    return $tag_ids;
}

add_action('init', 'register_post_types');
function register_post_types(){
    register_post_type('mytags', array(
        'label'  => null,
        'labels' => array(
            'name'               => 'Группы меток', // основное название для типа записи
            'singular_name'      => 'Группа', // название для одной записи этого типа
            'add_new'            => 'Добавить группу', // для добавления новой записи
            'add_new_item'       => 'Добавление группы', // заголовка у вновь создаваемой записи в админ-панели.
            'edit_item'          => 'Редактирование группы', // для редактирования типа записи
            'new_item'           => 'Новая группа', // текст новой записи
            'view_item'          => 'Смотреть группу', // для просмотра записи этого типа.
            'search_items'       => 'Искать группу', // для поиска по этим типам записи
            'not_found'          => 'Не найдено', // если в результате поиска ничего не было найдено
            'not_found_in_trash' => 'Не найдено в корзине', // если не было найдено в корзине
            'parent_item_colon'  => '', // для родителей (у древовидных типов)
            'menu_name'          => 'Группы меток', // название меню
        ),
        'description'         => '',
        'public'              => true,
        'publicly_queryable'  => null,
        'exclude_from_search' => null,
        'show_ui'             => null,
        'show_in_menu'        => null, // показывать ли в меню адмнки
        'show_in_admin_bar'   => null, // по умолчанию значение show_in_menu
        'show_in_nav_menus'   => null,
        'menu_position'       => 2,
        'menu_icon'           => null, 
        //'capability_type'   => 'post',
        //'capabilities'      => 'post', // массив дополнительных прав для этого типа записи
        //'map_meta_cap'      => null, // Ставим true чтобы включить дефолтный обработчик специальных прав
        'hierarchical'        => false,
        'supports'            => array('title','editor'), // 'title','editor','author','thumbnail','excerpt','trackbacks','custom-fields','comments','revisions','page-attributes','post-formats'
        'taxonomies'          => array('post_tag'),
        'has_archive'         => false,
        'rewrite'             => array( 'slug'=>'tag/%tagslist%', 'with_front'=>false, 'pages'=>false, 'feeds'=>false, 'feed'=>false ),
        'query_var'           => true,
    ) );
}

// генерация permalink для функции get_permalink()

add_filter('post_type_link', 'mytags_permalink', 1, 2);

function mytags_permalink( $permalink, $post ){
    // выходим если это не наш тип записи: без холдера '%tagslist%'
    if( strpos($permalink, '%tagslist%') === FALSE )
        return $permalink;
    $tags = get_tags_slug_array($post->ID);
    if ($tags)
        return home_url( 'tag/' . implode($tags,'+') );
    return home_url( 'tag' );
}

// Раздел "помощь" типа записи mytags
function call_seaClass()
{
    return new seaClass();
}

if ( is_admin() )
    add_action( 'load-post.php', 'call_seaClass' );
    add_action( 'load-post-new.php', 'call_seaClass' );

class seaClass
{
    const LANG = 'sea_textdomain';

    public function __construct()
    {
        add_action( 'add_meta_boxes', array( &$this, 'add_some_meta_box' ) );
    }

    public function add_some_meta_box()
    {
        add_meta_box(
             'sea_shortcodes_meta_box1'
            ,__( 'Помощь', self::LANG )
            ,array( &$this, 'render_meta_box_content1' )
            ,'mytags'
            ,'normal'
            ,'high'
        );
        add_meta_box(
             'sea_shortcodes_meta_box2'
            ,__( 'Помощь', self::LANG )
            ,array( &$this, 'render_meta_box_content2' )
            ,'post'
            ,'normal'
            ,'high'
        );
    }

    public function render_meta_box_content1()
    {
        ?>
        <div class='mydiv'>
            <p>Введите в заголовок описание тегов для себя. В текст введите описание которое будет отображаться на сайте.</p>
            <p>Не забудьте выбрать метки к которым будет привязано данное описание.</p>
            <p>В тексте и заголовке записи можно использовать шорткод <b>[year]</b></p>
        </div>
        <?php
    }

    public function render_meta_box_content2()
    {
        ?>
        <div class='mydiv'>
            <p>В тексте и заголовке записи можно использовать шорткод <b>[year]</b></p>
        </div>
        <?php
    }
}

function sea_year_shortcode( $atts ) {
    return date("Y");
}
add_shortcode( 'year', 'sea_year_shortcode' );

add_filter( 'the_title', 'filter_function_name_11', 10, 2 );
function filter_function_name_11( $title, $id ) {
    return do_shortcode($title);
}

?>