# MatnPala 3

![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)
![PHP Version](https://img.shields.io/badge/PHP-%3E%3D8.0-8892BF.svg)
![Status](https://img.shields.io/badge/Status-Active-success.svg)
![Version](https://img.shields.io/badge/Version-3.0.0-brightgreen.svg)

MatnPala is an independent, zero-dependency Natural Language Processing (NLP) engine designed for deep semantic understanding, structural analysis, and high-fidelity summarization of Persian (Farsi) text. 

Unlike conventional summarization tools that rely on heavy third-party APIs (e.g., OpenAI, Anthropic) or simple extractive cutting, MatnPala operates entirely offline on your own infrastructure. This ensures complete data privacy, ultra-low latency, and full control over the inference pipeline. The engine is built from the ground up to handle the unique morphological and syntactical complexities of the Persian language.

## Table of Contents
- [Architecture](#architecture)
- [Key Features](#key-features)
- [Requirements](#requirements)
- [Quick Start](#quick-start)
- [Evaluation Metrics](#evaluation-metrics)
- [License](#license)

---

## Architecture

The engine utilizes a hybrid, multi-layered pipeline combining statistical weighting with heuristic rules to preserve logical argumentation and conceptual integrity. The core is designed to process text dynamically, adapting its strategy based on the detected document type.

*   DocType Classifier: Automatically detects the domain of the input (Philosophy, Academic, News, Narrative, How-to) and dynamically adjusts the weighting matrix to optimize extraction.
*   Weight Engine (Statistical): Implements BM25 (K1=1.5, B=0.75), Cosine Similarity, and Position Priors (head/tail bias) to score sentence importance with high mathematical precision.
*   Tokenizer & Normalizer: Native Persian tokenization, stemming, stopword removal, and Arabic/Persian character unification (e.g., normalizing ي to ی, and ك to ک) to ensure clean linguistic processing.
*   Coreference Resolution: Detects anaphoras and pronouns to maintain textual cohesion and prevent fragmented summaries.
*   MMR (Maximal Marginal Relevance): Prunes redundant sentences to maximize information coverage while maintaining a low compression ratio.
*   Argument Mining: Identifies theses, premises, objections, and conclusions to preserve the logical skeleton of complex texts, ensuring that the core argument is never lost.

## Key Features

*   100% Offline & Private: No data ever leaves your server. Perfect for sensitive documents.
*   Zero External Dependencies: No reliance on paid APIs. Built entirely with native PHP and SQLite.
*   Adaptive Summarization: Understands the difference between a philosophical essay and a daily news report.
*   High Performance: Optimized for speed, capable of processing large texts in milliseconds.

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

The engine evaluates its own output based on structural and semantic preservation. Current internal benchmarks for version 3 (Philosophy domain):

| Metric | v2 | v3 |
| :--- | :---: | :---: |
| Semantic Independence | 45% | 85% |

*(Note: These are heuristic diagnostics, not human-rated gold standards. The goal is to reach 99.9% accuracy).*

## License

Distributed under the GNU General Public License v3.0 (GPL-3.0). See LICENSE for more information.

| Coherence & Readability | 55% | 80% |
| Logical Structure Preservation | 70% | 75% |
| Content Coverage | 80% | 70% |
