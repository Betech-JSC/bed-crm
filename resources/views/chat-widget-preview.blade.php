<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Preview: {{ $widget->name }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f3f4f6;
            min-height: 100vh;
        }
        .preview-header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 10000;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .preview-header h1 {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
        }
        .preview-header .actions {
            display: flex;
            gap: 8px;
        }
        .preview-header a, .preview-header button {
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            border: 1px solid #d1d5db;
            background: white;
            color: #374151;
            cursor: pointer;
            transition: all 0.2s;
        }
        .preview-header a:hover, .preview-header button:hover {
            background: #f9fafb;
            border-color: #9ca3af;
        }
        .preview-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 24px;
        }
        .info-banner {
            background: #dbeafe;
            border: 1px solid #93c5fd;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 24px;
        }
        .info-banner h3 {
            font-size: 16px;
            font-weight: 600;
            color: #1e40af;
            margin-bottom: 8px;
        }
        .info-banner p {
            font-size: 14px;
            color: #1e3a8a;
            line-height: 1.5;
        }
        .website-preview {
            background: white;
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 32px;
            min-height: 600px;
            position: relative;
        }
        .website-preview h2 {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 16px;
        }
        .website-preview p {
            color: #6b7280;
            margin-bottom: 24px;
            line-height: 1.6;
        }
        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
        .feature-card {
            background: #f9fafb;
            border-radius: 8px;
            padding: 20px;
        }
        .feature-card h3 {
            font-size: 16px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 8px;
        }
        .feature-card p {
            font-size: 14px;
            color: #6b7280;
        }
        .widget-info {
            margin-top: 24px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 16px;
        }
        .info-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 16px;
        }
        .info-card h4 {
            font-size: 14px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 12px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
            font-size: 14px;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-row span:first-child {
            color: #6b7280;
        }
        .info-row span:last-child {
            font-weight: 500;
            color: #1f2937;
        }
        .color-preview {
            display: inline-block;
            width: 24px;
            height: 24px;
            border-radius: 4px;
            border: 1px solid #d1d5db;
            vertical-align: middle;
            margin-right: 8px;
        }
    </style>
</head>
<body>
    <div class="preview-header">
        <h1>Preview: {{ $widget->name }}</h1>
        <div class="actions">
            <a href="{{ route('chat-widgets.edit', $widget->id) }}">← Back to Edit</a>
            <button onclick="window.close()">Close Preview</button>
        </div>
    </div>

    <div class="preview-content">
        <div class="info-banner">
            <h3>📱 Live Preview Mode</h3>
            <p>
                This is a live preview of your chat widget. The widget is fully functional - you can click the chat button 
                in the corner to open it and test all features including messaging, banners, and settings. 
                This simulates exactly how it will appear on your website.
            </p>
        </div>

        <div class="website-preview">
            <h2>Welcome to Our Website</h2>
            <p>
                This is a sample page to demonstrate how the chat widget appears on your website. 
                The chat widget button should appear in the <strong>{{ $widget->position === 'bottom-right' ? 'bottom-right' : 'bottom-left' }}</strong> corner.
                Click it to open the chat window and test the widget functionality.
            </p>

            <div class="feature-grid">
                <div class="feature-card">
                    <h3>Product Features</h3>
                    <p>Learn about our amazing product features and capabilities.</p>
                </div>
                <div class="feature-card">
                    <h3>Pricing Plans</h3>
                    <p>Choose the perfect plan for your business needs.</p>
                </div>
                <div class="feature-card">
                    <h3>Customer Support</h3>
                    <p>Get help from our friendly support team anytime.</p>
                </div>
            </div>

            <p style="color: #9ca3af; font-size: 14px;">
                💡 <strong>Tip:</strong> Scroll down to see more content. The chat widget stays fixed in position.
            </p>

            <!-- More content to demonstrate scrolling -->
            <div style="margin-top: 48px; padding: 32px; background: #f9fafb; border-radius: 8px;">
                <h3 style="font-size: 20px; margin-bottom: 16px;">About Us</h3>
                <p style="color: #6b7280; line-height: 1.8;">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. 
                    Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.
                </p>
            </div>

            <div style="margin-top: 32px; padding: 32px; background: #f9fafb; border-radius: 8px;">
                <h3 style="font-size: 20px; margin-bottom: 16px;">Contact Information</h3>
                <p style="color: #6b7280; line-height: 1.8;">
                    Feel free to reach out to us through the chat widget. Our AI assistant is ready to help you with any questions.
                </p>
            </div>
        </div>

        <div class="widget-info">
            <div class="info-card">
                <h4>Widget Settings</h4>
                <div class="info-row">
                    <span>Position:</span>
                    <span>{{ $widget->position === 'bottom-right' ? 'Bottom Right' : 'Bottom Left' }}</span>
                </div>
                <div class="info-row">
                    <span>Primary Color:</span>
                    <span>
                        <span class="color-preview" style="background-color: {{ $widget->primary_color }};"></span>
                        {{ $widget->primary_color }}
                    </span>
                </div>
                <div class="info-row">
                    <span>Collect Email:</span>
                    <span>{{ $widget->collect_email ? 'Yes' : 'No' }}</span>
                </div>
                <div class="info-row">
                    <span>Collect Phone:</span>
                    <span>{{ $widget->collect_phone ? 'Yes' : 'No' }}</span>
                </div>
            </div>

            <div class="info-card">
                <h4>Welcome Message</h4>
                <p style="color: #6b7280; font-style: italic; margin-top: 8px;">
                    {{ $widget->welcome_message ?: 'No welcome message configured' }}
                </p>
            </div>
        </div>
    </div>

    <!-- Load Chat Widget Script -->
    <script src="{{ $embedUrl }}"></script>
    
    <!-- Debug and ensure widget loads -->
    <script>
        console.log('Preview page loaded. Widget script URL:', '{{ $embedUrl }}');
        
        // Check if widget initialized
        function checkWidgetInit() {
            if (window.ChatWidget) {
                if (window.ChatWidget.isInitialized) {
                    console.log('✅ Chat Widget initialized successfully');
                    console.log('Widget Settings:', window.ChatWidget.widgetSettings);
                } else {
                    console.log('⏳ Chat Widget object exists but not yet initialized');
                    setTimeout(checkWidgetInit, 500);
                }
            } else {
                console.log('⏳ Waiting for ChatWidget to load...');
                setTimeout(checkWidgetInit, 500);
            }
        }
        
        // Start checking after a short delay
        setTimeout(checkWidgetInit, 1000);
        
        // Also check on window load
        window.addEventListener('load', function() {
            setTimeout(checkWidgetInit, 2000);
        });
    </script>
</body>
</html>

