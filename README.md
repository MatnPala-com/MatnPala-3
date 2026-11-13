<p align="center">
  <img src="https://raw.githubusercontent.com/MatnPala-com/MatnPala-3/refs/heads/main/Logo_MatnPala.jpg" alt="MatnPala Logo" width="150"/>
</p>

# MatnPala 3

![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)
![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.0-8892BF.svg)
![Status](https://img.shields.io/badge/Status-Active-success.svg)
![Version](https://img.shields.io/badge/Version-3.0.0-brightgreen.svg)

MatnPala is an independent, zero-dependency NLP engine designed for deep semantic understanding and summarization of Persian (Farsi) text. It operates entirely offline without relying on third-party APIs (e.g., OpenAI), ensuring complete data privacy, ultra-low latency, and full control over your data. 

## Table of Contents
- [Architecture](#architecture)
- [Requirements](#requirements)
- [Quick Start](#quick-start)
- [Evaluation Metrics](#evaluation-metrics)
- [License](#license)

---

## Architecture

The engine utilizes a hybrid pipeline combining statistical weighting with heuristic rules to preserve logical argumentation and conceptual integrity. 

*   DocType Classifier: Automatically detects the domain of the input (Philosophy, Academic, News, Narrative) and dynamically adjusts the weighting matrix.
*   Weight Engine (Statistical): Implements BM25, Cosine Similarity, and Position Priors to score sentence importance with high precision.
*   Tokenizer & Normalizer: Native Persian tokenization, stemming, stopword removal, and Arabic/Persian character unification (e.g., ي to ی, ك to ک).
*   Coreference Resolution: Detects pronouns and anaphoras to maintain textual cohesion.
*   MMR (Maximal Marginal Relevance): Prunes redundant sentences to maximize information coverage and prevent repetition.
*   Argument Mining: Identifies theses, premises, and conclusions to preserve the logical skeleton of complex texts.

## Requirements

*   PHP >= 8.0
*   PHP Extensions: pdo_sqlite, mbstring, curl
*   Web Server (Nginx / Apache) or CLI Environment

## Quick Start

1. Clone the repository:
bash
git clone https://github.com/YourUsername/MatnPala.git
cd MatnPala


2. Configure paths in vip.php:
php
define('DB_PATH', __DIR__ . '/data/db.sqlite');
define('WEIGHT_LANG_PATH', __DIR__ . '/lang/Weight');


3. Run the engine via PHP:
php
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


## Evaluation Metrics

Current internal benchmarks for version 3 (Philosophy domain):

| Metric | v2 | v3 |
| :--- | :---: | :---: |
| Semantic Independence | 45% | 85% |
| Coherence & Readability | 55% | 80% |
| Logical Structure Preservation | 70% | 75% |
| Content Coverage | 80% | 70% |

*(Note: These are heuristic diagnostics, not human-rated gold standards. The goal is to reach 99.9% accuracy).*

## License

Distributed under the GNU General Public License v3.0 (GPL-3.0). See LICENSE for more information.
