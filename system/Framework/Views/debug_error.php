<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exception: <?= htmlspecialchars($e->getMessage()) ?></title>
    <style>
        body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #1a202c; color: #e2e8f0; }
        .header { background: #2d3748; padding: 20px; border-bottom: 3px solid #e53e3e; }
        .header h1 { margin: 0; color: #e53e3e; font-size: 1.5rem; }
        .container { padding: 30px; max-width: 1200px; margin: 0 auto; }
        .error-summary { background: #2d3748; padding: 25px; border-radius: 8px; margin-bottom: 30px; border-left: 4px solid #e53e3e; }
        .error-summary h2 { margin-top: 0; color: #f7fafc; }
        .error-details { display: grid; gap: 20px; }
        .detail-section { background: #2d3748; padding: 20px; border-radius: 8px; }
        .detail-section h3 { margin-top: 0; color: #63b3ed; border-bottom: 1px solid #4a5568; padding-bottom: 10px; }
        .code-block { background: #1a202c; padding: 15px; border-radius: 6px; font-family: 'Courier New', monospace; font-size: 0.9rem; overflow-x: auto; border: 1px solid #4a5568; }
        .file-path { color: #68d391; }
        .line-number { color: #f6ad55; }
        .trace-item { margin-bottom: 15px; padding: 10px; background: #1a202c; border-radius: 6px; border-left: 3px solid #4299e1; }
        .context-data { color: #cbd5e0; }
        .highlight { background: #553c9a; padding: 2px 4px; border-radius: 3px; }
        @media (max-width: 768px) {
            .container { padding: 15px; }
            .code-block { font-size: 0.8rem; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🚫 <?= get_class($e) ?></h1>
    </div>
    
    <div class="container">
        <div class="error-summary">
            <h2>Error Message</h2>
            <p><strong><?= htmlspecialchars($e->getMessage()) ?></strong></p>
            <p class="file-path">
                <span class="highlight"><?= htmlspecialchars($e->getFile()) ?></span>
                on line <span class="line-number"><?= $e->getLine() ?></span>
            </p>
        </div>
        
        <div class="error-details">
            <div class="detail-section">
                <h3>📋 Exception Details</h3>
                <div class="code-block">
                    <strong>Class:</strong> <?= get_class($e) ?><br>
                    <strong>Code:</strong> <?= $e->getCode() ?><br>
                    <?php if (method_exists($e, 'getStatusCode')): ?>
                    <strong>HTTP Status:</strong> <?= $e->getStatusCode() ?><br>
                    <?php endif; ?>
                    <strong>Time:</strong> <?= date('Y-m-d H:i:s') ?>
                </div>
            </div>
            
            <?php if (method_exists($e, 'getContext')): ?>
            <div class="detail-section">
                <h3>🔍 Context Information</h3>
                <div class="code-block">
                    <pre><?= htmlspecialchars(json_encode($e->getContext(), JSON_PRETTY_PRINT)) ?></pre>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="detail-section">
                <h3>📚 Stack Trace</h3>
                <?php foreach ($e->getTrace() as $index => $trace): ?>
                    <div class="trace-item">
                        <strong>#<?= $index ?></strong> 
                        <?php if (isset($trace['file'])): ?>
                            <span class="file-path"><?= htmlspecialchars($trace['file']) ?></span>
                            <span class="line-number">(<?= $trace['line'] ?? 'unknown' ?>)</span>
                        <?php endif; ?>
                        <br>
                        <?php if (isset($trace['class'])): ?>
                            <code><?= htmlspecialchars($trace['class']) ?><?= $trace['type'] ?? '::' ?></code>
                        <?php endif; ?>
                        <code><?= htmlspecialchars($trace['function']) ?>()</code>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <?php if (isset($_SERVER)): ?>
            <div class="detail-section">
                <h3>🌐 Request Information</h3>
                <div class="code-block">
                    <strong>Method:</strong> <?= $_SERVER['REQUEST_METHOD'] ?? 'Unknown' ?><br>
                    <strong>URI:</strong> <?= $_SERVER['REQUEST_URI'] ?? 'Unknown' ?><br>
                    <strong>User Agent:</strong> <?= htmlspecialchars($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') ?><br>
                    <strong>IP:</strong> <?= $_SERVER['REMOTE_ADDR'] ?? 'Unknown' ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>