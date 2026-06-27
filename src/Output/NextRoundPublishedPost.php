<?php

declare(strict_types=1);

namespace SwissChess\Output;

class NextRoundPublishedPost extends WordpressOutput {

    public function createNextRoundNews(array $pairings): int
    {
        $lastRound = end($pairings);
        $roundNumber = $lastRound[0]['round'];

        $title = "Stadtmeisterschaft – Runde {$roundNumber} ausgelost";

        $content  = "<p>Die Paarungen der <strong>Runde {$roundNumber}</strong> stehen fest.</p>";
        $content .= $this->pairingsToHtmlTable([$lastRound]);

        // Optional: Termin-Hinweis
        $content .= "<p>Viel Erfolg allen Teilnehmern!</p>";

        $postId = wp_insert_post([
            'post_title'   => $title,
            'post_content' => $content,
            'post_status'  => 'publish',
            'post_type'    => 'post',
        ]);

        return $postId;
    }
}