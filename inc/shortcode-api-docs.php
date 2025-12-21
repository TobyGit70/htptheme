<?php
/**
 * API Documentation Shortcode
 *
 * Renders the API documentation
 *
 * Usage: [api_docs]
 */

function happyturtle_api_docs_shortcode() {
    ob_start();
    ?>
    <div style="max-width:1000px;margin:0 auto;padding:0 2rem;">

        <!-- Getting Started -->
        <div style="background:white;border-radius:16px;padding:3rem;margin-bottom:2rem;box-shadow:0 4px 20px rgba(0,0,0,0.08);">
            <h2 style="color:#1B4332;margin-bottom:1rem;">Getting Started</h2>

            <h3 style="color:#2D6A4F;margin-top:2rem;margin-bottom:1rem;">Base URL</h3>
            <pre style="background:#1e293b;color:#e2e8f0;padding:1rem;border-radius:8px;overflow-x:auto;"><code>https://happyturtleprocessing.com/wp-json/happyturtle/v1</code></pre>

            <h3 style="color:#2D6A4F;margin-top:2rem;margin-bottom:1rem;">Authentication</h3>
            <p style="margin-bottom:1rem;">All API requests require authentication using API Key and API Secret in the request headers:</p>
            <pre style="background:#1e293b;color:#e2e8f0;padding:1rem;border-radius:8px;overflow-x:auto;"><code>X-API-Key: your_api_key
X-API-Secret: your_api_secret</code></pre>

            <div style="background:#fff3cd;border-left:4px solid #ffc107;padding:1rem;margin-top:1rem;border-radius:4px;">
                <strong>⚠️ Important:</strong> Store your API credentials securely. Never expose them in client-side code or public repositories.
            </div>
        </div>

        <!-- Products -->
        <div style="background:white;border-radius:16px;padding:3rem;margin-bottom:2rem;box-shadow:0 4px 20px rgba(0,0,0,0.08);">
            <h2 style="color:#1B4332;margin-bottom:1.5rem;">Products API</h2>

            <div style="margin-bottom:3rem;">
                <h3 style="color:#2D6A4F;margin-bottom:1rem;">GET /products</h3>
                <p style="margin-bottom:1rem;">Retrieve all available products</p>

                <strong>Query Parameters:</strong>
                <ul style="margin:1rem 0;">
                    <li><code>category</code> (optional) - Filter by category</li>
                </ul>

                <strong>Example Request:</strong>
                <pre style="background:#1e293b;color:#e2e8f0;padding:1rem;border-radius:8px;overflow-x:auto;margin-top:0.5rem;"><code>curl -X GET "https://happyturtleprocessing.com/wp-json/happyturtle/v1/products" \
  -H "X-API-Key: your_api_key" \
  -H "X-API-Secret: your_api_secret"</code></pre>
            </div>

            <div>
                <h3 style="color:#2D6A4F;margin-bottom:1rem;">GET /categories</h3>
                <p style="margin-bottom:1rem;">Get all product categories</p>
            </div>
        </div>

        <!-- Orders -->
        <div style="background:white;border-radius:16px;padding:3rem;margin-bottom:2rem;box-shadow:0 4px 20px rgba(0,0,0,0.08);">
            <h2 style="color:#1B4332;margin-bottom:1.5rem;">Orders API</h2>

            <div style="margin-bottom:3rem;">
                <h3 style="color:#2D6A4F;margin-bottom:1rem;">POST /orders</h3>
                <p style="margin-bottom:1rem;">Create a new order</p>

                <strong>Request Body:</strong>
                <pre style="background:#1e293b;color:#e2e8f0;padding:1rem;border-radius:8px;overflow-x:auto;margin-top:0.5rem;"><code>{
  "items": [
    {"product_id": 1, "quantity": 10}
  ]
}</code></pre>
            </div>

            <div>
                <h3 style="color:#2D6A4F;margin-bottom:1rem;">GET /orders</h3>
                <p style="margin-bottom:1rem;">Retrieve your orders</p>
            </div>
        </div>

        <!-- Support -->
        <div style="background:linear-gradient(135deg,#1B4332,#2D6A4F,#D4A574);border-radius:16px;padding:3rem;color:white;text-align:center;">
            <h2 style="margin-bottom:1rem;">Need Help?</h2>
            <p style="margin-bottom:2rem;">Our B2B support team is here to help with integration and technical questions.</p>
            <a href="/contact/" style="display:inline-block;background:white;color:#1B4332;padding:1rem 2rem;border-radius:8px;text-decoration:none;font-weight:700;">Contact Support</a>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('api_docs', 'happyturtle_api_docs_shortcode');
