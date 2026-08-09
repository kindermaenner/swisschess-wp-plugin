<?php

declare(strict_types=1);

namespace SwissChess\Output;

if (!defined('ABSPATH')) {
    exit;
}

class PublishedPostOutput extends WordpressOutput
{
    protected function resolveTemplatePost(string $templateName)
    {
        if ($templateName === '') {
            return null;
        }

        $templatePost = get_page_by_title($templateName, OBJECT, 'page');
        if ($templatePost) {
            return $templatePost;
        }

        $templatePost = get_page_by_title($templateName, OBJECT, 'post');
        if ($templatePost) {
            return $templatePost;
        }

        if (function_exists('get_page_by_path')) {
            $slug = sanitize_title($templateName);

            $templatePost = get_page_by_path($slug, OBJECT, 'page');
            if ($templatePost) {
                return $templatePost;
            }

            $templatePost = get_page_by_path($slug, OBJECT, 'post');
            if ($templatePost) {
                return $templatePost;
            }
        }

        return null;
    }

    protected function createOrUpdatePostFromTemplate(string $title, string $postSlug, string $metaKey, string $content, int $templatePostId): int
    {
        $existing = get_posts([
            'post_type' => 'post',
            'meta_key' => $metaKey,
            'meta_value' => '1',
            'numberposts' => 1,
        ]);

        if ($existing) {
            $postId = (int)$existing[0]->ID;

            wp_update_post([
                'ID' => $postId,
                'post_title' => $title,
                'post_name' => $postSlug,
                'post_content' => $content,
                'post_status' => 'publish',
                'post_author' => get_option('swisschess_author') ?: 1,
            ]);
        } else {
            $postId = wp_insert_post([
                'post_title' => $title,
                'post_name' => $postSlug,
                'post_content' => $content,
                'post_status' => 'publish',
                'post_type' => 'post',
                'post_author' => get_option('swisschess_author') ?: 1,
            ]);

            if ($postId > 0) {
                update_post_meta($postId, $metaKey, '1');
            }
        }

        if ($postId > 0) {
            $this->copyAllMeta($templatePostId, $postId);
            $this->copyCategoriesWithoutTemplateCategory($templatePostId, $postId);
        }

        return $postId;
    }
}
