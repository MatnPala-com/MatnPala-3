# MatnPala 3

<p align="center">
  <img src="https://raw.githubusercontent.com/MatnPala-com/MatnPala-3/refs/heads/main/Logo_MatnPala.jpg" alt="MatnPala Logo" width="160" style="border-radius: 24px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);" />
</p>

![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)
![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.0-8892BF.svg)
![Status](https://img.shields.io/badge/Status-Active-success.svg)
![Version](https://img.shields.io/badge/Version-3.0.0-brightgreen.svg)

**MatnPala** is an advanced, independent, and zero-dependency NLP engine meticulously designed for deep semantic understanding and high-fidelity summarization of Persian (Farsi) text. 

In an era where most AI tools rely heavily on external APIs (like OpenAI or Anthropic), MatnPala operates entirely offline on your own infrastructure. This architecture guarantees complete data privacy, ultra-low latency, and absolute control over the inference pipeline. The engine is built from the ground up to tackle the unique morphological and syntactical complexities of the Persian language, ensuring that the core meaning is never lost.

## Table of Contents
- [Architecture](#architecture)
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

The engine evaluates its own output based on structural and semantic preservation. Current internal benchmarks for version 3 (Philosophy domain) show significant improvements:

<table style="border-collapse: separate; border-spacing: 0; width: 100%; border-radius: 12px; overflow: hidden; border: 1px solid #e1e4e8;">
  <thead style="background-color: #f6f8fa;">
    <tr>
      <th style="padding: 12px; text-align: left;">Metric</th>
      <th style="padding: 12px; text-align: center;">v2</th>
      <th style="padding: 12px; text-align: center;">v3</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td style="padding: 12px; border-top: 1px solid #e1e4e8;">Semantic Independence</td>
      <td style="padding: 12px; text-align: center; border-top: 1px solid #e1e4e8;">45%</td>
      <td style="padding: 12px; text-align: center; border-top: 1px solid #e1e4e8;"><b>85%</b></td>
    </tr>
    <tr>
      <td style="padding: 12px; border-top: 1px solid #e1e4e8;">Coherence & Readability</td>
      <td style="padding: 12px; text-align: center; border-top: 1px solid #e1e4e8;">55%</td>
      <td style="padding: 12px; text-align: center; border-top: 1px solid #e1e4e8;"><b>80%</b></td>
    </tr>
    <tr>
      <td style="padding: 12px; border-top: 1px solid #e1e4e8;">Logical Structure Preservation</td>
      <td style="padding: 12px; text-align: center; border-top: 1px solid #e1e4e8;">70%</td>
      <td style="padding: 12px; text-align: center; border-top: 1px solid #e1e4e8;"><b>75%</b></td>
    </tr>
    <tr>
      <td style="padding: 12px; border-top: 1px solid #e1e4e8;">Content Coverage</td>
      <td style="padding: 12px; text-align: center; border-top: 1px solid #e1e4e8;">80%</td>
      <td style="padding: 12px; text-align: center; border-top: 1px solid #e1e4e8;"><b>70%</b></td>
    </tr>
  </tbody>
</table>

*(Note: These are heuristic diagnostics, not human-rated gold standards. The goal is to reach 99.9% accuracy).*

## License

Distributed under the GNU General Public License v3.0 (GPL-3.0). See LICENSE for more information.
