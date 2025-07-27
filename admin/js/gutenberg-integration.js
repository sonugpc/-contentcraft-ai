/**
 * Gutenberg Integration for ContentCraft AI
 */

(function() {
    'use strict';
    
    // This file is no longer needed as the functionality is moved to a meta box.
    // The file is kept to avoid breaking the build process.
    // All functionality is now handled by the meta box and its associated scripts.
    
    // Add styles for Gutenberg integration
    const style = document.createElement('style');
    style.textContent = `
        .contentcraft-ai-gutenberg-panel {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .contentcraft-ai-enhance-btn,
        .contentcraft-ai-generate-btn {
            width: 100%;
            margin-bottom: 10px;
            justify-content: center;
        }
        
        .contentcraft-ai-floating-button {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 9999;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 20px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .contentcraft-ai-floating-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.5);
        }
        
        .contentcraft-ai-floating-button .button-text {
            display: none;
        }
        
        .contentcraft-ai-floating-button:hover .button-text {
            display: inline;
        }
        
        .contentcraft-ai-floating-button .dashicons {
            font-size: 20px;
            width: 20px;
            height: 20px;
        }
        
        @media (max-width: 768px) {
            .contentcraft-ai-floating-button {
                bottom: 20px;
                right: 20px;
                padding: 12px 15px;
            }
        }
    `;
    
    document.head.appendChild(style);
    
})();
