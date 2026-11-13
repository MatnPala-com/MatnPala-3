```markdown
<p align="center">
  <img src="https://raw.githubusercontent.com/MatnPala-com/MatnPala-3/refs/heads/main/Logo_MatnPala.jpg" alt="MatnPala Logo" width="150" style="border-radius: 12px;" />
</p>

<h1 align="center">MatnPala 3</h1>

<p align="center">
  <img src="https://img.shields.io/badge/License-GPLv3-blue.svg" alt="License: GPL v3" />
  <img src="https://img.shields.io/badge/PHP-%3E%3D8.0-8892BF.svg" alt="PHP Version" />
  <img src="https://img.shields.io/badge/Status-Active-success.svg" alt="Status" />
  <img src="https://img.shields.io/badge/Version-3.0.0-brightgreen.svg" alt="Version" />
</p>

MatnPala is an independent, zero-dependency NLP engine built for deep semantic understanding and summarization of Persian (Farsi) text. Unlike conventional summarizers that rely on external cloud services, MatnPala runs entirely offline and does not depend on any third-party API (e.g., OpenAI). This design guarantees complete data privacy, ultra-low latency, and full control over your data, making it suitable for sensitive, academic, and philosophical corpora where confidentiality and reproducibility matter most.

The engine is written in pure PHP and follows a modular architecture, so each stage of the pipeline — from normalization to final sentence selection — can be inspected, replaced, or extended without touching the core. It is designed to preserve the logical argumentation and conceptual integrity of long-form Persian texts, rather than producing shallow keyword-based extracts.

## Table of Contents
- [Architecture](#architecture)
- [Requirements](#requirements)
- [Quick Start](#quick-start)
- [Evaluation Metrics](#evaluation-metrics)
- [License](#license)

---

## Architecture

The engine utilizes a hybrid pipeline combining statistical weighting with heuristic rules to preserve logical argumentation and conceptual integrity.

- **DocType Classifier**: Automatically detects the domain of the input (Philosophy, Academic, News, Narrative) and dynamically adjusts the weighting matrix.
- **Weight Engine (Statistical)**: Implements BM25, Cosine Similarity, and Position Priors to score sentence importance with high precision.
- **Tokenizer & Normalizer**: Native Persian tokenization, stemming, stopword removal, and Arabic/Persian character unification (e.g., ي to ی, ك to ک).
- **Coreference Resolution**: Detects pronouns and anaphoras to maintain textual cohesion.
- **MMR (Maximal Marginal Relevance)**: Prunes redundant sentences to maximize information coverage and prevent repetition.
- **Argument Mining**: Identifies theses, premises, and conclusions to preserve the logical skeleton of complex texts.

## Requirements

- PHP >= 8.0
- PHP Extensions: pdo_sqlite, mbstring, curl
- Web Server (Nginx / Apache) or CLI Environment

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

Current internal benchmarks for version 3 (Philosophy domain):

<table align="center" style="border-radius: 6px; overflow: hidden; border-collapse: separate; border-spacing: 0;">
  <thead>
    <tr>
      <th align="left">Metric</th>
      <th align="center">v2</th>
      <th align="center">v3</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td align="left">Semantic Independence</td>
      <td align="center">45%</td>
      <td align="center">85%</td>
    </tr>
    <tr>
      <td align="left">Coherence &amp; Readability</td>
      <td align="center">55%</td>
      <td align="center">80%</td>
    </tr>
    <tr>
      <td align="left">Logical Structure Preservation</td>
      <td align="center">70%</td>
      <td align="center">75%</td>
    </tr>
    <tr>
      <td align="left">Content Coverage</td>
      <td align="center">80%</td>
      <td align="center">70%</td>
    </tr>
  </tbody>
</table>

*(Note: These are heuristic diagnostics, not human-rated gold standards. The goal is to reach 99.9% accuracy.)*

## License

Distributed under the GNU General Public License v3.0 (GPL-3.0). See LICENSE for more information.
```
