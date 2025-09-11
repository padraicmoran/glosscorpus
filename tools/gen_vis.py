import os
from bs4 import BeautifulSoup
import json


def gather_data(verbose=False):
    """Gathers data relevant to visualisations being generated from TEI files"""

    # Map Directories
    tools_dir = os.getcwd()
    tools_parent = os.path.dirname(tools_dir)
    texts_dir = os.path.join(tools_parent, "data", "texts")

    if verbose:
        print("Currently gathering data from base-text files\n")

    # Collect tokenised base-text paths
    basetext_paths = dict()
    if os.path.isdir(texts_dir):
        base_texts = os.listdir(texts_dir)
        for base_text in base_texts:
            txt_dir = os.path.join(texts_dir, base_text)
            base_text_path = os.path.join(txt_dir, "basetext.xml")
            if os.path.isfile(base_text_path):
                tokenised = False
                pre_tokenised = False
                with open(base_text_path, "r", encoding="utf-8") as xml_file:
                    content = xml_file.read()
                base_soup = BeautifulSoup(content, "xml")
                revision_desc = base_soup.find("teiHeader").find("revisionDesc")
                if revision_desc:
                    changes = revision_desc.find_all("change")
                    for change in changes:
                        tok_check = change["what"]
                        if tok_check == "tokenisation_and_annotation":
                            tokenised = True
                            break
                        elif tok_check == "pre_tokenisation":
                            pre_tokenised = True
                            break
                if tokenised or pre_tokenised:
                    basetext_paths[base_text] = base_text_path
                elif verbose:
                    print(f"  No tokenised file found for base-text: {base_text}")
            elif verbose:
                print(f"  No tokenised file found for base-text: {base_text}")
    elif verbose:
        print(f"Could not find file path:\n    {texts_dir}\nNo tokenised base-text files found\n")

    if len(basetext_paths) == 0 and verbose:
        print("No tokenised base-text files found.\n    Cannot collect word-counts for any base-text.\n")

    # Collect lemma-annotated gloss edition paths
    glosstext_paths = dict()
    if os.path.isdir(texts_dir):
        base_texts = os.listdir(texts_dir)
        for base_text in base_texts:
            if verbose:
                print(f"Currently finding gloss collections for base-text: {base_text}\n")
            txt_dir = os.path.join(texts_dir, base_text, "gloss_collections")
            text_glosses = dict()
            if os.path.isdir(txt_dir):
                gloss_editions = os.listdir(txt_dir)
                for g_ed in gloss_editions:
                    ed_name = "".join(g_ed.split(".xml"))
                    gloss_file_path = os.path.join(txt_dir, g_ed)
                    if os.path.isfile(gloss_file_path):
                        lemmatised = False
                        pre_lemmatised = False
                        with open(gloss_file_path, "r", encoding="utf-8") as xml_file:
                            content = xml_file.read()
                        gloss_soup = BeautifulSoup(content, "xml")
                        revision_desc = gloss_soup.find("teiHeader").find("revisionDesc")
                        if revision_desc:
                            changes = revision_desc.find_all("change")
                            for change in changes:
                                tok_check = change["what"]
                                if tok_check == "lemma_assignment":
                                    lemmatised = True
                                    break
                                elif tok_check == "pre_lemma_assignment":
                                    pre_lemmatised = True
                                    break
                        if lemmatised or pre_lemmatised:
                            text_glosses[ed_name] = gloss_file_path
                        elif verbose:
                            print(f"  No lemma-annotated file found for gloss collection: {ed_name}")
                    elif verbose:
                        print(f"  No lemma-annotated file found for gloss collection: {ed_name}")
            elif verbose:
                print(f"  No gloss_collections directory found for base-text: {base_text}")
            if text_glosses:
                glosstext_paths[base_text] = text_glosses
            elif verbose:
                print(f"  No lemma-annotated gloss edition files found for base-text: {base_text}\n")

    elif verbose:
        print(f"Could not find file path:\n    {texts_dir}\nNo lemma-annotated collections files found\n")

    if len(glosstext_paths) == 0 and verbose:
        print("No lemma-annotated gloss edition files found.\n    Cannot collect gloss data for any base-text.\n")

    if verbose:
        print("\nExtracting information from collected texts...\n")

    # Create dictionary to contain all output
    output = dict()

    # Extract information from base-text files
    for base_text in basetext_paths:
        words_dict = dict()
        toktext_path = basetext_paths.get(base_text)
        with open(toktext_path, 'r', encoding="utf-8") as xml_file:
            content = xml_file.read()
        base_soup = BeautifulSoup(content, 'lxml-xml')
        words = base_soup.find("text").find_all("w")
        words_dict["words"] = words
        output[base_text] = words_dict

    # Extract information from gloss collections
    for base_text in glosstext_paths:
        collections = glosstext_paths.get(base_text)
        base_dict = output.get(base_text)
        coll_dict = dict()
        base_dict["collections"] = coll_dict
        for collection_name in collections:
            coll_dict = base_dict.get("collections")
            gloss_dict = dict()
            collection_path = collections.get(collection_name)
            with open(collection_path, 'r', encoding="utf-8") as xml_file:
                content = xml_file.read()
            gloss_soup = BeautifulSoup(content, 'lxml-xml')
            glosses = gloss_soup.find("text").find_all("note", target=True)
            glosses = [
                note for note in glosses if not (
                    note.has_attr("type") and note["type"] in [
                        "editorial",
                        "translation"
                    ]
                )
            ]
            gloss_dict["glosses"] = glosses
            coll_dict[collection_name] = gloss_dict
            base_dict["collections"] = coll_dict
            output[base_text] = base_dict

    return output


def calculate_base_results(corpus_data, verbose=False):
    """Uses data gathered from TEI files to find information about text collections"""

    # Get list of words, and associated collections' information for each base-text
    for base_text in corpus_data:
        base_dict = corpus_data.get(base_text)
        if not base_dict:
            if verbose:
                print(f"No information gathered for base-text file: {base_text}"
                      f"\n  Cannot compile all data for this text\n")
            continue

        # Get the list of tokenised words from each base-text
        words = base_dict.get("words")
        if not words:
            if verbose:
                print(f"No lemmata found for base-text file: {base_text}"
                      f"\n  Cannot compile all data for this text\n")
            continue

        # Collect all lemmata and line numbers from this base-text
        base_lemma_list = list()
        base_line_list = list()

        # Determine the number of words in a base-text
        word_count = len(words)
        base_dict["word_count"] = word_count

        # Determine the number of lines in a base-text
        word_ids = [word["xml:id"] for word in words]
        all_lines = [word_id if "__" not in word_id else word_id.split("__")[0] for word_id in word_ids]
        all_lines = sorted(list(set(all_lines)))
        line_count = len(all_lines)
        base_dict["line_count"] = line_count
        corpus_data[base_text] = base_dict

        # Get data for each collection associated with a single base-text
        collections = base_dict.get("collections")
        if not collections:
            if verbose:
                print(f"No collections found for base-text file: {base_text}"
                      f"\n  Cannot compile all data for this text\n")
            continue
        else:
            for coll_name in collections:
                coll_dict = collections.get(coll_name)

                # Get a list of glosses from a collection
                glosses = coll_dict.get("glosses")
                if not glosses:
                    if verbose:
                        print(
                            f"No glosses found for collection: {base_text}"
                            f"\n  Cannot compile data for this collection\n")
                    continue
                else:

                    # Determine the number of glosses in an edition.
                    gloss_count = len(glosses)
                    coll_dict["gloss_count"] = gloss_count

                    # Find all of the target lemmata in this collection
                    targets = [gloss["target"] for gloss in glosses]
                    targets.sort()
                    coll_dict["lemmata"] = targets

                    # Find the number of words which are lemmata
                    unique_targets = sorted(list(set(targets)))
                    num_lemmata = len(unique_targets)
                    coll_dict["glossed_lemmata"] = num_lemmata

                    # Determine the number of glosses per lemma
                    glosses_per_lemma = {lemma: targets.count(lemma) for lemma in unique_targets}
                    coll_dict["glosses_per_lemma"] = glosses_per_lemma

                    # Find all of the target lines in this collection
                    target_lines = [target if "__" not in target else target.split("__")[0] for target in targets]
                    target_lines.sort()
                    coll_dict["lines"] = target_lines

                    # Find the number of lines which have been glossed
                    unique_lines = sorted(list(set(target_lines)))
                    glossed_lines = len(unique_lines)
                    coll_dict["glossed_lines"] = glossed_lines

                    # Determine the number of glosses per line
                    glosses_per_line = {line: target_lines.count(line) for line in unique_lines}
                    coll_dict["glosses_per_line"] = glosses_per_line

                    # Store all collected data back to the corpus_data dictionary
                    collections[coll_name] = coll_dict
                    base_dict["collections"] = collections
                    corpus_data[base_text] = base_dict

                    # Add lemmata and lines from each collection associated with a single base-text to lists
                    base_lemma_list.extend(targets)
                    base_line_list.extend(target_lines)

        # Determine the number of tokens per base-text which are lemmata
        base_lemma_list = sorted(list(set(base_lemma_list)))
        lemma_count = len(base_lemma_list)
        base_dict["lemma_count"] = lemma_count

        # Determine the percentage of words which are lemmata
        lemma_pc = lemma_count/word_count
        lemma_pc *= 100
        base_dict["lemma_percent"] = round(lemma_pc, 2)

        # Determine the number of lines which contain lemmata
        base_line_list = sorted(list(set(base_line_list)))
        glossed_line_count = len(base_line_list)
        base_dict["glossed_line_count"] = glossed_line_count

        # Determine the percentage of lines which contain lemmata
        line_pc = glossed_line_count / line_count
        line_pc *= 100
        base_dict["glossed_line_percent"] = round(line_pc, 2)

        corpus_data[base_text] = base_dict

    return corpus_data


def calculate_combined_results(base_results, verbose=False):
    """Combines initially extracted information for individual collections
       to generate results from multiple gloss collections on a single base-text"""

    all_combined_lems = dict()
    all_combined_lines = dict()
    all_combined_gplems = dict()
    all_combined_gplines = dict()

    for bt in base_results:
        bt_dict = base_results.get(bt)
        collections = bt_dict.get("collections")
        combined_lemmata = list()
        combined_lines = list()
        combined_gplem = dict()
        combined_gpline = dict()

        if len(collections) <= 1:
            if verbose:
                print(f"Fewer than 2 gloss collections found for base-text: {bt}"
                      f"\n  Cannot combine results from multiple collections\n")

        for coll in collections:
            coll_dict = collections.get(coll)
            if "lemmata" in coll_dict:
                combined_lemmata.extend(coll_dict.get("lemmata"))
            if "lines" in coll_dict:
                combined_lines.extend(coll_dict.get("lines"))
            if "glosses_per_lemma" in coll_dict:
                gplem_data = coll_dict.get("glosses_per_lemma")
                max_gplem = max(gplem_data, key=gplem_data.get)
                max_gplem = {max_gplem: gplem_data.get(max_gplem)}
                combined_gplem[coll] = max_gplem
            if "glosses_per_line" in coll_dict:
                gpline_data = coll_dict.get("glosses_per_line")
                max_gpline = max(gpline_data, key=gpline_data.get)
                max_gpline = {max_gpline: gpline_data.get(max_gpline)}
                combined_gpline[coll] = max_gpline
        all_combined_lems[bt] = combined_lemmata
        all_combined_lines[bt] = combined_lines
        all_combined_gplems[bt] = combined_gplem
        all_combined_gplines[bt] = combined_gpline

    # Determine the lemma with the highest number of glosses (among all collections)
    for bt in all_combined_lems:
        combined_lemmata = all_combined_lems.get(bt)
        if combined_lemmata:
            all_lemmata = sorted(list(set(combined_lemmata)))
            combined_lemma_counts = dict()
            for lemma in all_lemmata:
                combined_lemma_counts[lemma] = combined_lemmata.count(lemma)
            max_clemc = max(combined_lemma_counts, key=combined_lemma_counts.get)
            max_clemc = {max_clemc: combined_lemma_counts.get(max_clemc)}
            subdict = base_results.get(bt)
            subdict["most_glossed_lemma"] = max_clemc

    # Determine the line with the highest number of glosses (among all collections)
    for bt in all_combined_lines:
        combined_lines = all_combined_lines.get(bt)
        if combined_lines:
            all_lines = sorted(list(set(combined_lines)))
            combined_line_counts = dict()
            for line in all_lines:
                combined_line_counts[line] = combined_lines.count(line)
            max_clinec = max(combined_line_counts, key=combined_line_counts.get)
            max_clinec = {max_clinec: combined_line_counts.get(max_clinec)}
            subdict = base_results.get(bt)
            subdict["most_glossed_line"] = max_clinec

    # Determine the lemma with the highest number of glosses (in each individual collection)
    for bt in all_combined_gplems:
        collections = all_combined_gplems.get(bt)
        subdict = base_results.get(bt)
        subdict_collections = subdict.get("collections")
        if collections:
            for coll in collections:
                coll_subdict = subdict_collections.get(coll)
                most_glossed_lemma = collections.get(coll)
                coll_subdict["most_glossed_lemma"] = most_glossed_lemma

    # Determine the line with the highest number of glosses (in each individual collection)
    for bt in all_combined_gplines:
        collections = all_combined_gplines.get(bt)
        subdict = base_results.get(bt)
        subdict_collections = subdict.get("collections")
        if collections:
            for coll in collections:
                coll_subdict = subdict_collections.get(coll)
                most_glossed_line = collections.get(coll)
                coll_subdict["most_glossed_line"] = most_glossed_line

    combined_results = base_results

    return combined_results


def trim_data(compiled_data, verbose=False):
    """Remove bulky data that isn't needed anymore once corpus information has been compiled"""

    if verbose:
        print("\nRemoving unnecessary data before creating output file\n")

    for bt in compiled_data:
        bt_dict = compiled_data.get(bt)
        if "words" in bt_dict:
            del bt_dict["words"]
        if "collections" in bt_dict:
            collections = bt_dict.get("collections")
            for coll in collections:
                coll_dict = collections.get(coll)
                if "glosses" in coll_dict:
                    del coll_dict["glosses"]
                if "lemmata" in coll_dict:
                    del coll_dict["lemmata"]
                if "lines" in coll_dict:
                    del coll_dict["lines"]
                if "glosses_per_lemma" in coll_dict:
                    del coll_dict["glosses_per_lemma"]
                if "glosses_per_line" in coll_dict:
                    del coll_dict["glosses_per_line"]

    return compiled_data


def save_info(compiled_data, verbose=False):
    """Creates a JSON file of the information required to create visualisations"""

    if verbose:
        print("Saving visualisation information for gloss collections!")

    save_dir = os.path.join(os.getcwd(), "visualisation_data")
    os.makedirs(save_dir, exist_ok=True)
    with open(os.path.join(save_dir, "collections_info.json"), "w") as json_file:
        json.dump(trim_data(compiled_data), json_file, indent=4, default=str)


if __name__ == "__main__":

    gathered_data = gather_data(True)
    corpus_base_results = calculate_base_results(gathered_data, True)
    corpus_results = calculate_combined_results(corpus_base_results, True)

    save_info(corpus_results, True)
