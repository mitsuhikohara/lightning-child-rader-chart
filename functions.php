<?php
/**
 * Lightning Child theme functions
 *
 * @package lightning
 */

/************************************************
 * 独自CSSファイルの読み込み処理
 *
 * 主に CSS を SASS で 書きたい人用です。 素の CSS を直接書くなら style.css に記載してかまいません.
 */

// 独自のCSSファイル（assets/css/）を読み込む場合は true に変更してください.
$my_lightning_additional_css = false;

if ( $my_lightning_additional_css ) {
	// 公開画面側のCSSの読み込み.
	add_action(
		'wp_enqueue_scripts',
		function() {
			wp_enqueue_style(
				'my-lightning-custom',
				get_stylesheet_directory_uri() . '/assets/css/style.css',
				array( 'lightning-design-style' ),
				filemtime( dirname( __FILE__ ) . '/assets/css/style.css' )
			);
		}
	);
	// 編集画面側のCSSの読み込み.
	add_action(
		'enqueue_block_editor_assets',
		function() {
			wp_enqueue_style(
				'my-lightning-editor-custom',
				get_stylesheet_directory_uri() . '/assets/css/editor.css',
				array( 'wp-edit-blocks', 'lightning-gutenberg-editor' ),
				filemtime( dirname( __FILE__ ) . '/assets/css/editor.css' )
			);
		}
	);
}

/************************************************
 * Rader Chart
 */
function childtheme_enqueue_scripts() {
    // Chart.js をCDNから読み込み
    wp_enqueue_script(
        'chartjs',
        'https://cdn.jsdelivr.net/npm/chart.js',
        array(),
        null,
        true
    );
}
add_action('wp_enqueue_scripts', 'childtheme_enqueue_scripts');

// functions.php に追加

function radar_chart_shortcode($atts) {
    $atts = shortcode_atts(
        array(
            'labels' => 'SEO,デザイン,速度,安全性,拡張性',
            'data' => '80,70,90,85,75',
            'width' => '300',
            'height' => '300',
            'label' => 'サイト評価',
        ),
        $atts,
        'radar_chart'
    );

    $labels = explode(',', $atts['labels']);
    $data = explode(',', $atts['data']);
    $chart_id = 'radar_' . uniqid(); // ← 自動生成ID

    ob_start();
    ?>
<canvas id="<?php echo esc_attr($chart_id); ?>"
        width="<?php echo esc_attr($atts['width']); ?>"
        height="<?php echo esc_attr($atts['height']); ?>"></canvas>

<div id="total-score-<?php echo esc_attr($chart_id); ?>"></div>
<div id="score-table-<?php echo esc_attr($chart_id); ?>"></div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const labels = <?php echo json_encode($labels); ?>;
    const dataValues = <?php echo json_encode($data); ?>;
    const totalScore = dataValues.reduce((a, b) => a + Number(b), 0);
    const maxScore = dataValues.length * 10;

    // 総合得点表示
    document.getElementById('total-score-<?php echo esc_attr($chart_id); ?>')
        .innerText = '総合得点: ' + totalScore + ' / ' + maxScore;

    // 各項目の点数を表形式で表示
    let html = '<table style="border-collapse:collapse; margin-top:10px;">';
    html += '<tr><th style="border:1px solid #ccc; padding:4px;">項目</th><th style="border:1px solid #ccc; padding:4px;">点数</th></tr>';
    for (let i = 0; i < labels.length; i++) {
        html += '<tr><td style="border:1px solid #ccc; padding:4px;">' + labels[i] + '</td>';
        html += '<td style="border:1px solid #ccc; padding:4px;">' + dataValues[i] + '</td></tr>';
    }
    html += '</table>';
    document.getElementById('score-table-<?php echo esc_attr($chart_id); ?>').innerHTML = html;

    // Chart描画
    const ctx = document.getElementById('<?php echo esc_attr($chart_id); ?>');
    if (Chart.getChart(ctx)) {
        Chart.getChart(ctx).destroy();
    }

new Chart(ctx, {
    type: 'radar',
    data: {
        labels: labels,
        datasets: [{
            label: '<?php echo esc_js($atts['label']); ?>',
            data: dataValues,
            fill: true,
            backgroundColor: 'rgba(54, 162, 235, 0.2)',
            borderColor: 'rgb(54, 162, 235)',
            pointBackgroundColor: 'rgb(54, 162, 235)'
        }]
    },
    options: {
        responsive: false,
        scales: {
            r: {
                min: 0,
                max: 10,
                ticks: {
                    display: false // ← 軸の数値を非表示
                },
                pointLabels: {
                    font: {
                        size: 14
                    }
                }
            }
        },
        plugins: {
            tooltip: { enabled: true }
        }
    },
plugins: [{
    id: 'valueLabelPlugin',
    afterDatasetsDraw(chart) {
        const { ctx, data } = chart;
        const meta = chart.getDatasetMeta(0);
        const dataset = data.datasets[0];

        // 平均値を計算
        const avg = dataset.data.reduce((a, b) => a + Number(b), 0) / dataset.data.length;

        ctx.save();
        ctx.font = '12px Arial';
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';

        meta.data.forEach((point, index) => {
            const value = dataset.data[index];
            const text = value.toString();

            // 円の半径
            const radius = 14;

            // 背景色を条件分岐
            if (value == 10) {
                ctx.fillStyle = 'gold'; // 満点は金色
            } else if (value >= avg) {
                ctx.fillStyle = 'rgba(0,200,0,0.8)'; // 平均以上は緑
            } else {
                ctx.fillStyle = 'rgba(200,0,0,0.8)'; // 平均未満は赤
            }

            // 背景の円を描画
            ctx.beginPath();
            ctx.arc(point.x, point.y, radius, 0, 2 * Math.PI);
            ctx.fill();

            // 枠線
            ctx.strokeStyle = 'rgba(0,0,0,0.3)';
            ctx.stroke();

            // 数値を描画
            ctx.fillStyle = 'white'; // 背景色に合わせて文字は白
            ctx.fillText(text, point.x, point.y);
        });

        ctx.restore();
    }
}]
});