<?php
/**
 * Partner Portal Shortcode
 *
 * Renders the partner registration form
 *
 * Usage: [partner_portal]
 */

function happyturtle_partner_portal_shortcode() {
    ob_start();
    ?>
    <div id="partner-portal-app" style="max-width:1200px;margin:2rem auto;padding:0 2rem;">
        <div style="background:white;border-radius:16px;padding:3rem;box-shadow:0 4px 20px rgba(0,0,0,0.08);">

            <div id="registration-section">
                <h2 style="color:#1B4332;margin-bottom:1.5rem;">Partner Registration</h2>
                <p style="margin-bottom:2rem;color:#1e293b;">Complete this form to register for API access. Your application will be reviewed and you'll receive API credentials once approved.</p>

                <form id="partner-registration-form" style="display:grid;gap:1.5rem;">
                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
                        <div>
                            <label style="display:block;font-weight:600;margin-bottom:0.5rem;color:#1B4332;">Business Name *</label>
                            <input type="text" name="business_name" required style="width:100%;padding:0.75rem;border:1px solid #2D6A4F;border-radius:8px;">
                        </div>
                        <div>
                            <label style="display:block;font-weight:600;margin-bottom:0.5rem;color:#1B4332;">Contact Name *</label>
                            <input type="text" name="contact_name" required style="width:100%;padding:0.75rem;border:1px solid #2D6A4F;border-radius:8px;">
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
                        <div>
                            <label style="display:block;font-weight:600;margin-bottom:0.5rem;color:#1B4332;">Email *</label>
                            <input type="email" name="email" required style="width:100%;padding:0.75rem;border:1px solid #2D6A4F;border-radius:8px;">
                        </div>
                        <div>
                            <label style="display:block;font-weight:600;margin-bottom:0.5rem;color:#1B4332;">Phone *</label>
                            <input type="tel" name="phone" required style="width:100%;padding:0.75rem;border:1px solid #2D6A4F;border-radius:8px;">
                        </div>
                    </div>

                    <div>
                        <label style="display:block;font-weight:600;margin-bottom:0.5rem;color:#1B4332;">Address *</label>
                        <input type="text" name="address" required style="width:100%;padding:0.75rem;border:1px solid #2D6A4F;border-radius:8px;">
                    </div>

                    <div style="display:grid;grid-template-columns:2fr 1fr 1fr;gap:1.5rem;">
                        <div>
                            <label style="display:block;font-weight:600;margin-bottom:0.5rem;color:#1B4332;">City *</label>
                            <input type="text" name="city" required style="width:100%;padding:0.75rem;border:1px solid #2D6A4F;border-radius:8px;">
                        </div>
                        <div>
                            <label style="display:block;font-weight:600;margin-bottom:0.5rem;color:#1B4332;">State *</label>
                            <input type="text" name="state" value="AR" required style="width:100%;padding:0.75rem;border:1px solid #2D6A4F;border-radius:8px;">
                        </div>
                        <div>
                            <label style="display:block;font-weight:600;margin-bottom:0.5rem;color:#1B4332;">ZIP *</label>
                            <input type="text" name="zip" required style="width:100%;padding:0.75rem;border:1px solid #2D6A4F;border-radius:8px;">
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1.5rem;">
                        <div>
                            <label style="display:block;font-weight:600;margin-bottom:0.5rem;color:#1B4332;">License Number *</label>
                            <input type="text" name="license_number" required style="width:100%;padding:0.75rem;border:1px solid #2D6A4F;border-radius:8px;">
                        </div>
                        <div>
                            <label style="display:block;font-weight:600;margin-bottom:0.5rem;color:#1B4332;">License Type *</label>
                            <select name="license_type" required style="width:100%;padding:0.75rem;border:1px solid #2D6A4F;border-radius:8px;">
                                <option value="">Select...</option>
                                <option value="dispensary">Dispensary</option>
                                <option value="cultivator">Cultivator</option>
                                <option value="processor">Processor</option>
                            </select>
                        </div>
                        <div>
                            <label style="display:block;font-weight:600;margin-bottom:0.5rem;color:#1B4332;">EIN *</label>
                            <input type="text" name="ein" required style="width:100%;padding:0.75rem;border:1px solid #2D6A4F;border-radius:8px;" placeholder="XX-XXXXXXX">
                        </div>
                    </div>

                    <div>
                        <label style="display:block;font-weight:600;margin-bottom:0.5rem;color:#1B4332;">BioTrack License (Optional)</label>
                        <input type="text" name="biotrack_license" style="width:100%;padding:0.75rem;border:1px solid #2D6A4F;border-radius:8px;" placeholder="Your BioTrack license if different">
                    </div>

                    <div style="margin-top:1rem;">
                        <button type="submit" style="background:linear-gradient(135deg,#1B4332,#2D6A4F,#D4A574);color:white;border:none;border-radius:8px;padding:1rem 2rem;font-weight:700;cursor:pointer;transition:all 0.3s ease;">
                            Submit Registration
                        </button>
                    </div>
                </form>

                <div id="registration-message" style="display:none;margin-top:2rem;padding:1.5rem;border-radius:8px;"></div>
            </div>

            <div style="margin-top:3rem;padding-top:3rem;border-top:2px solid #e5e7eb;">
                <h3 style="color:#1B4332;margin-bottom:1rem;">Developer Resources</h3>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">
                    <a href="/api-docs/" style="padding:1.5rem;background:#f8fafc;border-radius:8px;text-decoration:none;color:#1B4332;border:1px solid #e5e7eb;transition:all 0.3s ease;">
                        <strong style="display:block;margin-bottom:0.5rem;color:#2D6A4F;">📚 API Documentation</strong>
                        Complete API reference and code examples
                    </a>
                    <a href="/contact/" style="padding:1.5rem;background:#f8fafc;border-radius:8px;text-decoration:none;color:#1B4332;border:1px solid #e5e7eb;transition:all 0.3s ease;">
                        <strong style="display:block;margin-bottom:0.5rem;color:#2D6A4F;">✉️ Contact Support</strong>
                        Questions? Contact our B2B team
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var form = document.getElementById('partner-registration-form');
        if (!form) return;

        form.addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(e.target);
            const data = Object.fromEntries(formData.entries());

            const messageDiv = document.getElementById('registration-message');
            messageDiv.style.display = 'none';

            try {
                const response = await fetch('/wp-json/happyturtle/v1/register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data)
                });

                const result = await response.json();

                if (result.success) {
                    messageDiv.style.backgroundColor = '#d4edda';
                    messageDiv.style.color = '#155724';
                    messageDiv.style.border = '1px solid #c3e6cb';
                    messageDiv.innerHTML = '<strong>Success!</strong> ' + result.message;
                    e.target.reset();
                } else {
                    throw new Error(result.message || 'Registration failed');
                }

                messageDiv.style.display = 'block';
            } catch (error) {
                messageDiv.style.backgroundColor = '#f8d7da';
                messageDiv.style.color = '#721c24';
                messageDiv.style.border = '1px solid #f5c6cb';
                messageDiv.innerHTML = '<strong>Error:</strong> ' + error.message;
                messageDiv.style.display = 'block';
            }
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('partner_portal', 'happyturtle_partner_portal_shortcode');
