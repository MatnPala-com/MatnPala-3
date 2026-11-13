<div align="center">

  <img src="https://raw.githubusercontent.com/MatnPala-com/MatnPala-3/refs/heads/main/Logo_MatnPala.jpg" alt="MatnPala Logo" width="160" style="border-radius: 20px; margin-bottom: 10px;" />

  <h1>MatnPala 3</h1>

  <p>
    <img src="https://img.shields.io/badge/License-GPLv3-blue.svg" alt="License" />
    <img src="https://img.shields.io/badge/PHP-%3E%3D8.0-8892BF.svg" alt="PHP Version" />
    <img src="https://img.shields.io/badge/Status-Active-success.svg" alt="Status" />
    <img src="https://img.shields.io/badge/Version-3.0.0-brightgreen.svg" alt="Version" />
  </p>

  <p><b>An independent, zero-dependency NLP engine designed for deep semantic understanding and high-fidelity summarization of Persian (Farsi) text.</b></p>

</div>

---

**MatnPala** operates entirely offline on your own infrastructure. Unlike tools that rely on external APIs (such as OpenAI or Anthropic), this architecture guarantees complete data privacy, ultra-low latency, and absolute control over the inference pipeline. The engine is built from the ground up to tackle the unique morphological and syntactical complexities of the Persian language, ensuring that the core meaning and logical flow of the original text are never lost.

## Table of Contents
- [Architecture](#architecture)
- [About](#about)
- [Requirements](#requirements)
- [Quick Start](#quick-start)
- [Evaluation Metrics](#evaluation-metrics)
- [License](#license)

---

## Architecture

The engine utilizes a hybrid, multi-layered pipeline that combines statistical weighting with heuristic rules to preserve logical argumentation and conceptual integrity. The system dynamically adapts its strategy based on the detected document type.

*   **DocType Classifier:** Automatically detects the domain of the input (Philosophy, Academic, News, Narrative, How-to) and dynamically adjusts the weighting matrix to optimize the extraction process.
*   **Weight Engine (Statistical):** Implements advanced algorithms like BM25 (K1=1.5, B=0.75), Cosine Similarity, and Position Priors to score sentence importance with high mathematical precision.
*   **Tokenizer & Normalizer:** Native Persian tokenization, stemming, stopword removal, and Arabic/Persian character unification (e.g., normalizing ي to ی, and ك to ک) to ensure clean and accurate linguistic processing.
*   **Coreference Resolution:** Detects anaphoras and pronouns to maintain textual cohesion and prevent fragmented summaries.
*   **MMR (Maximal Marginal Relevance):** Prunes redundant sentences to maximize information coverage while maintaining a low and efficient compression ratio.
*   **Argument Mining:** Identifies theses, premises, objections, and conclusions to preserve the logical skeleton of complex texts.

## About

MatnPala was built from the ground up to solve a fundamental problem: most AI tools treat Persian as a secondary language, relying on heavy translation layers or external APIs. We believe the Persian language deserves a native, independent, and privacy-first NLP engine. 

Designed and developed entirely offline, MatnPala represents a commitment to linguistic accuracy, data privacy, and architectural minimalism.

## Requirements

*   PHP >= 8.0
*   PHP Extensions: `pdo_sqlite`, `mbstring`, `curl`
*   Web Server (Nginx / Apache) or CLI Environment

## Quick Start

1. Clone the repository:

```bash
git clone https://github.com/YourUsername/MatnPala.git
cd MatnPala
```

2. Configure paths in `vip.php`:

```php
define('DB_PATH', __DIR__ . '/data/db.sqlite');
define('WEIGHT_LANG_PATH', __DIR__ . '/lang/Weight');
```

3. Run the engine via PHP:

```php
require 'vip.php';

$text = "Your long Persian text goes here...";
$engine = new Summarizer();

// Parameters: text, ratio (0.2 to 0.9), language (optional)
$result = $engine->run($text, 0.5, 'fa');

if ($result['success']) {
    echo $result['summary'];
    
    // Output heuristic evaluation metrics (JSON)
    print_r($result['stats']['quality']); 
}
```

## Evaluation Metrics

The engine evaluates its own output based on structural and semantic preservation. Current internal benchmarks for version 3 (Philosophy domain) show significant improvements over previous versions:

| Metric | v2 | v3 |
| :--- | :---: | :---: |
| Semantic Independence | 45% | **85%** |
| Coherence & Readability | 55% | **80%** |
| Logical Structure Preservation | 70% | **75%** |
| Content Coverage | 80% | **70%** |

*(Note: These are heuristic diagnostics, not human-rated gold standards. The goal is to reach 99.9% accuracy).*

## License

Distributed under the GNU General Public License v3.0 (GPL-3.0). See LICENSE for more information.
