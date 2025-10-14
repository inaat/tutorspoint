<?php
/**
 * Template Name: Blog Page
 * Blog Page Template - Tutors Point
 *
 * @package Astra Child
 */

// Exit if accessed directly
if (!defined('ABSPATH')) exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog - <?php bloginfo('name'); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=League+Spartan:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <?php wp_head(); ?>
</head>
<body <?php body_class('blog-page'); ?>>

<div class="front-page-wrapper">
    <div class="bg-blur blur-1"></div>
    <div class="bg-blur blur-2"></div>
    <div class="bg-blur blur-3"></div>

    <?php include(get_stylesheet_directory() . '/oheader.php'); ?>

    <div class="content-wrapper">

        <!-- Blog Header -->
        <section class="blog-header">
            <h1 class="page-title">Blog</h1>
            <p class="page-subtitle">Insights, tips, and resources for students and tutors</p>
        </section>

        <!-- Blog Filters -->
        <section class="blog-filters">
            <div class="filter-section">
                <div class="filter-grid">
                    <div class="filter-field">
                        <label>Category</label>
                        <select id="blog-category" onchange="filterBlog()">
                            <option value="">All Categories</option>
                            <?php
                            $categories = get_categories(['hide_empty' => true]);
                            $selected_cat = isset($_GET['cat']) ? $_GET['cat'] : '';
                            foreach ($categories as $cat):
                            ?>
                                <option value="<?php echo esc_attr($cat->slug); ?>" <?php selected($selected_cat, $cat->slug); ?>>
                                    <?php echo esc_html($cat->name); ?> (<?php echo $cat->count; ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-field">
                        <label>Search</label>
                        <input type="text" id="blog-search" placeholder="Search articles..." value="<?php echo isset($_GET['s']) ? esc_attr($_GET['s']) : ''; ?>" onkeypress="handleSearchEnter(event)">
                    </div>

                    <div class="filter-field" style="display:flex; align-items:flex-end;">
                        <button class="btn-filter" onclick="filterBlog()">Filter</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Blog Grid -->
        <section class="blog-section">
            <?php
            // Build query args
            $paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
            $args = [
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => 9,
                'paged' => $paged,
            ];

            // Add category filter
            if (isset($_GET['cat']) && !empty($_GET['cat'])) {
                $args['category_name'] = sanitize_text_field($_GET['cat']);
            }

            // Add search
            if (isset($_GET['s']) && !empty($_GET['s'])) {
                $args['s'] = sanitize_text_field($_GET['s']);
            }

            $blog_query = new WP_Query($args);

            if ($blog_query->have_posts()): ?>
                <div class="blog-grid">
                    <?php while ($blog_query->have_posts()): $blog_query->the_post(); ?>
                        <?php
                        $post_id = get_the_ID();
                        $categories = get_the_category();
                        $cat_name = !empty($categories) ? $categories[0]->name : 'Uncategorized';
                        $cat_slug = !empty($categories) ? $categories[0]->slug : '';
                        $author = get_the_author();
                        $date = get_the_date('M d, Y');
                        $excerpt = has_excerpt() ? get_the_excerpt() : wp_trim_words(get_the_content(), 20);
                        $featured_img = get_the_post_thumbnail_url($post_id, 'large');
                        $default_img = get_stylesheet_directory_uri() . '/assets/blog-default.jpg';
                        ?>
                        <article class="blog-card">
                            <a href="<?php the_permalink(); ?>" class="blog-card-link">
                                <div class="blog-image" style="background-image: url('<?php echo $featured_img ? esc_url($featured_img) : $default_img; ?>');">
                                    <span class="blog-category-badge <?php echo esc_attr($cat_slug); ?>">
                                        <?php echo esc_html($cat_name); ?>
                                    </span>
                                </div>
                                <div class="blog-content">
                                    <div class="blog-meta">
                                        <span class="blog-author"><?php echo esc_html($author); ?></span>
                                        <span class="blog-separator">•</span>
                                        <span class="blog-date"><?php echo esc_html($date); ?></span>
                                    </div>
                                    <h3 class="blog-title"><?php the_title(); ?></h3>
                                    <p class="blog-excerpt"><?php echo esc_html($excerpt); ?></p>
                                    <span class="blog-read-more">Read More →</span>
                                </div>
                            </a>
                        </article>
                    <?php endwhile; ?>
                </div>

                <!-- Pagination -->
                <div class="blog-pagination">
                    <?php
                    $big = 999999999;
                    echo paginate_links([
                        'base' => str_replace($big, '%#%', esc_url(get_pagenum_link($big))),
                        'format' => '?paged=%#%',
                        'current' => max(1, $paged),
                        'total' => $blog_query->max_num_pages,
                        'prev_text' => '← Previous',
                        'next_text' => 'Next →',
                        'type' => 'list',
                    ]);
                    ?>
                </div>

            <?php else:
                // Show dummy blog posts if no real posts found
                $dummy_posts = [
                    ['title' => 'Top 10 Study Tips for GCSE Success', 'author' => 'Sarah Johnson', 'date' => 'Oct 12, 2025', 'category' => 'Study Tips', 'excerpt' => 'Discover proven strategies to excel in your GCSE exams with expert guidance from experienced tutors.', 'image' => 'https://images.unsplash.com/photo-1434030216411-0b793f4b4173?w=600'],
                    ['title' => 'How Online Tutoring Transforms Learning', 'author' => 'Michael Davies', 'date' => 'Oct 10, 2025', 'category' => 'Education', 'excerpt' => 'Learn how personalized online tutoring can help students achieve their academic goals faster.', 'image' => 'https://images.unsplash.com/photo-1501504905252-473c47e087f8?w=600'],
                    ['title' => 'A-Level Maths: Common Mistakes to Avoid', 'author' => 'Dr. Emma Wilson', 'date' => 'Oct 08, 2025', 'category' => 'Mathematics', 'excerpt' => 'Avoid these common pitfalls in A-Level Mathematics and boost your exam performance significantly.', 'image' => 'https://images.unsplash.com/photo-1635070041078-e363dbe005cb?w=600'],
                    ['title' => 'Preparing for Your First Online Lesson', 'author' => 'John Smith', 'date' => 'Oct 05, 2025', 'category' => 'Getting Started', 'excerpt' => 'Everything you need to know before your first online tutoring session to make it a success.', 'image' => 'https://images.unsplash.com/photo-1522202176988-66273c2fd55f?w=600'],
                    ['title' => 'The Science Behind Effective Learning', 'author' => 'Prof. Rachel Green', 'date' => 'Oct 03, 2025', 'category' => 'Science', 'excerpt' => 'Understanding how the brain learns can help you study smarter, not harder. Discover the science.', 'image' => 'https://images.unsplash.com/photo-1532094349884-543bc11b234d?w=600'],
                    ['title' => 'Mastering Essay Writing for English GCSE', 'author' => 'Laura Thompson', 'date' => 'Sep 28, 2025', 'category' => 'English', 'excerpt' => 'Learn the techniques and strategies to write compelling essays that score top marks in GCSE English.', 'image' => 'https://images.unsplash.com/photo-1455390582262-044cdead277a?w=600'],
                    ['title' => 'Time Management Tips for Students', 'author' => 'David Brown', 'date' => 'Sep 25, 2025', 'category' => 'Productivity', 'excerpt' => 'Balance your studies, hobbies, and social life with these practical time management strategies.', 'image' => 'https://images.unsplash.com/photo-1484480974693-6ca0a78fb36b?w=600'],
                    ['title' => 'Understanding Chemistry Concepts: A Guide', 'author' => 'Dr. James Parker', 'date' => 'Sep 22, 2025', 'category' => 'Science', 'excerpt' => 'Break down complex chemistry concepts into simple, easy-to-understand explanations.', 'image' => 'https://images.unsplash.com/photo-1603126857599-f6e157fa2fe6?w=600'],
                    ['title' => 'How Parents Can Support Learning at Home', 'author' => 'Amanda Clarke', 'date' => 'Sep 20, 2025', 'category' => 'For Parents', 'excerpt' => 'Practical ways parents can create a supportive learning environment and encourage academic success.', 'image' => 'https://images.unsplash.com/photo-1491438590914-bc09fcaaf77a?w=600']
                ];
                ?>
                <div class="blog-grid">
                    <?php foreach ($dummy_posts as $post): ?>
                        <article class="blog-card">
                            <a href="#" class="blog-card-link">
                                <div class="blog-image" style="background-image: url('<?php echo esc_url($post['image']); ?>');">
                                    <span class="blog-category-badge">
                                        <?php echo esc_html($post['category']); ?>
                                    </span>
                                </div>
                                <div class="blog-content">
                                    <div class="blog-meta">
                                        <span class="blog-author"><?php echo esc_html($post['author']); ?></span>
                                        <span class="blog-separator">•</span>
                                        <span class="blog-date"><?php echo esc_html($post['date']); ?></span>
                                    </div>
                                    <h3 class="blog-title"><?php echo esc_html($post['title']); ?></h3>
                                    <p class="blog-excerpt"><?php echo esc_html($post['excerpt']); ?></p>
                                    <span class="blog-read-more">Read More →</span>
                                </div>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif;
            wp_reset_postdata();
            ?>
        </section>

      

        <!-- Custom Footer -->
        <?php include(get_stylesheet_directory() . "/ofooter.php"); ?>

    </div>
</div>

<!-- Login/Signup Modal -->
<?php echo do_shortcode('[tp_auth_portal]'); ?>

<script>
function filterBlog() {
    const category = document.getElementById('blog-category').value;
    const search = document.getElementById('blog-search').value;

    let url = '<?php echo get_permalink(); ?>';
    const params = new URLSearchParams();

    if (category) {
        params.append('cat', category);
    }

    if (search) {
        params.append('s', search);
    }

    const queryString = params.toString();
    if (queryString) {
        url += '?' + queryString;
    }

    window.location.href = url;
}

function handleSearchEnter(event) {
    if (event.key === 'Enter') {
        filterBlog();
    }
}
</script>

<?php wp_footer(); ?>
</body>
</html>
