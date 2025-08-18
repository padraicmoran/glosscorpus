import os
from collections import Counter
import fnmatch
from nltk import edit_distance as ed
from bs4 import BeautifulSoup, Comment
import re


def norm_ld(s1, s2):
    """Calculate the percentage difference between two strings"""

    lev_dist = ed(s1, s2)  # Get the edit distance

    l1 = len(s1)
    l2 = len(s2)
    max_dif = max(l1, l2)  # Find the length of the larger of the two strings (this is the max possible edit distance)

    lev_norm = (lev_dist/max_dif)*100  # Normalise the edit distance, then render as a percentage of difference
    lev_norm = round(lev_norm, 2)  # Round to two decimal places

    return lev_norm


def find_lems(verbose=True):
    """
    Searches for all tokenised .xml files in the first-level subdirectories of the directory this .py file is placed in,
    and links individual glosses to the lemmata.

    Where it is not possible to find the correct lemma, it marks the lemma as unknown.
    """

    # Search for tokenised .xml files in the base-texts directory
    base_files = {}
    tools_dir = os.getcwd()
    tools_parent = os.path.dirname(tools_dir)
    texts_dir = os.path.join(tools_parent, "data", "texts")
    if os.path.isdir(texts_dir):  # Check if it's a directory
        base_texts = os.listdir(texts_dir)
        for base_txt in base_texts:
            txt_dir = os.path.join(texts_dir, base_txt)
            base_txt_path = os.path.join(txt_dir, "basetext_tokenised.xml")  # Need better solution (!!!)
            if os.path.isfile(base_txt_path):
                if os.path.join("isidore", "basetext_tokenised.xml") in base_txt_path:
                    if verbose:
                        print(f"Skipping lemma annotation of file as its format is incompatible:"
                              f"\n    {base_txt_path}\n")
                    continue
                base_files[base_txt] = base_txt_path
            elif verbose:
                print(f"Skipping lemma annotation of glosses for {base_txt} "
                      f"as the tokenised base-text file could not be found:\n    {base_txt_path}\n")
    elif verbose:
        print(f"Could not find directory:\n    {texts_dir}\n")

    # For each base-text .xml file found
    for basefile in base_files:

        if verbose:
            print(f"Currently working on:\n    base-text: {basefile}\n")

        # Open and read the content of the base-text .xml file
        with open(base_files.get(basefile), 'r', encoding="utf-8") as base_xml_file:
            base_content = base_xml_file.read()
        base_soup = BeautifulSoup(base_content, 'lxml-xml')
        base_lines = base_soup.find_all("ab")
        line_check = [line.get("xml:id") for line in base_lines]
        duplicates = [item for item, count in Counter(line_check).items() if count > 1]
        if duplicates:
            if verbose:
                print("  The following line IDs were found to have duplicates:")
                for dup in duplicates:
                    print(f"    {dup}")
                print("  This will impact the assignment of lemmata, all these lines should be manually checked\n")
        base_lines = {line.get("xml:id"): line.find_all("w") for line in base_lines}

        # Collect paths to gloss collections' locations
        gloss_collections = []
        glosses_path = os.path.join(texts_dir, basefile, "gloss_collections")
        if os.path.isdir(glosses_path):  # Check if it's a directory
            for filename in fnmatch.filter(os.listdir(glosses_path), '*.xml'):
                if "_lemmatised" not in filename:  # Need better solution to ensure files not already lemmtised (!!!)
                    gloss_file_path = os.path.join(glosses_path, filename)
                    lemmatised_file_path = gloss_file_path[:-4] + "_lemmatised" + ".xml"
                    if os.path.isfile(gloss_file_path):
                        if os.path.isfile(lemmatised_file_path):
                            if verbose:
                                print(f"  Skipping annotation for the file, {filename}, "
                                      f"as an annotated file already exists:\n    {lemmatised_file_path}\n")
                            continue
                        gloss_collections.append(gloss_file_path)
                    else:
                        raise RuntimeError(f"Could not find file path: {gloss_file_path}")
        else:
            raise RuntimeError(f"Could not find file path: {glosses_path}")

        # For each collection of glosses found
        for gloss_file in gloss_collections:
            if verbose:
                print(f"  Currently annotating glosses from file:\n    {gloss_file}\n")

            # Open and read the content of the xml file
            with open(gloss_file, 'r', encoding="utf-8") as gloss_xml_file:
                gloss_content = gloss_xml_file.read()
            gloss_soup = BeautifulSoup(gloss_content, 'lxml-xml')

            # Create output text that can be altered from original
            output_text = gloss_content[:]

            # If no lemmata are tagged in the edition, skip over it
            if len(gloss_soup.find_all("term")) == 0:
                if verbose:
                    print(f"  No lemmata found in edition; abandoning attempt.\n")
                continue

            # Find each sequential <note> tag in the text file used to identify glosses and their lemmata
            gloss_attributes = {"n", "target", "facs", "ana"}
            gloss_tags = gloss_soup.find_all(
                lambda tag: tag.name == "note" and any(attr in tag.attrs for attr in gloss_attributes)
            )
            gloss_tags = [str(tag) for tag in gloss_tags]
            gloss_tags = [tag[:tag.find(">")+1] for tag in gloss_tags]
            # Replace entities silently removed by BeautifulSoup which need to be expanded
            gloss_tags = [
                tag if "facs=" not in tag else 'facs="&facsBase;'.join(tag.split('facs="')) for tag in gloss_tags
            ]

            if verbose:
                print("  Finding glosses...")

            # Create a list of glosses identified in the original text, with their indices
            # Format: (original text, start, end, lemma, target_line, new_line)
            identified_glosses = list()

            # Use BS to find unique IDs for each collected gloss
            skip_count = 0
            cur_line = ""
            for note in gloss_tags:
                bs_note = BeautifulSoup(note, "xml").note
                note_id = bs_note.get('n')
                if not note_id:
                    raise RuntimeError(f"Could not find ID for identified gloss:\n\n{bs_note}\n\n{note}")

                # Replace problematic XML quote character form note when it doesn't occur in the original_text
                if '"' in note_id:
                    note_id = '&quot;'.join(note_id.split('"'))

                lemma_note = ""
                # Use regex to find the note in the original text using the unique gloss ID
                # (DOTALL matches across multiple lines)
                note_pattern = re.compile(
                    rf'(<note\b[^>]*\bn\s*=\s*["\']{re.escape(note_id)}["\'][^>]*>.*?</note>)', re.DOTALL
                )

                match = note_pattern.search(output_text)
                if not match:
                    if verbose:
                        print(f"    Skipping gloss {note_id} as no gloss with this ID could be found in the text file:"
                              f"\n      {note}")
                    skip_count += 1
                    # lemma_note = f"<!-- re-examine: Could not locate gloss {note_id} in text file -->"
                    continue

                # Get the start and end indices of the match found in the original text
                span = [i for i in match.span()]
                original_note = match.group(1)
                note_count = original_note.count("<note")
                endnote_count = 1

                # Because <note> tags can be nested, the regex may not have found the correct closing tag: </note>
                # Iterate over the original text until the correct closing tag is found by counting the number of
                # opening and closing tags within each match, and updating the match indices until the number is even
                if note_count > endnote_count:
                    while note_count > endnote_count:
                        span = [span[0], output_text.find("</note>", span[1]) + len("</note>")]
                        original_note = output_text[span[0]:span[1]]
                        note_count = original_note.count("<note")
                        endnote_count = original_note.count("</note>")
                elif note_count != endnote_count:
                    raise RuntimeError("Could not find any closing tag.")

                # Parse the gloss (between <note> tags) with BS to more easily extract text and attributes
                note_soup = BeautifulSoup(original_note, "xml")
                full_note = note_soup.find("note")

                # Multiple <gloss> and <term> tags can occur between <note> tags
                # If more than one of either is used, ignore this gloss.
                note_glosses = full_note.find_all("gloss")
                note_terms = full_note.find_all("term")
                if len(note_glosses) > 1 or len(note_terms) > 1:
                    if verbose:
                        print(f"    Skipping gloss number {full_note["n"]}; "
                              f"multiple glosses or lemmata were detected between a single set of XML <note> tags")
                    skip_count += 1
                    # lemma_note = (f"<!-- re-examine: More than one <gloss> or <term> tags occur in the gloss: "
                    #               f"{target_line_id} -->")
                    continue

                # If no lemma is identified by a gloss
                if len(note_terms) == 0:
                    if verbose:
                        print(f"    Skipping gloss number {full_note["n"]}; "
                              f"no lemma was detected between the XML <note> tags associated with this gloss")
                    skip_count += 1
                    # lemma_note = (f"<!-- re-examine: No <term> tags occur in the gloss: {target_line_id} -->")
                    continue

                # If one lemma is identified by a gloss, preprocess it for later comparison
                elif len(note_terms) == 1:
                    # Isolate the lemma identified by the gloss
                    fg_lemma = note_terms[0].get_text()
                    fg_lemma = fg_lemma[fg_lemma.find(">") + 1:]
                    # Remove undesirable characters and tags from the identified lemma
                    undesirable_chars = ["[", "]", "*", "+", "(", ")", "<add>", "</add>", "<ex>", "</ex>", ".."]
                    for ud_char in undesirable_chars:
                        fg_lemma = "".join(fg_lemma.split(ud_char))
                    if "<del>" in fg_lemma:
                        fg_lemma = fg_lemma[:fg_lemma.find("<del>")] + fg_lemma[fg_lemma.find("</del>") + 6:]
                    # Replace abbreviations with full forms in the identified lemma
                    fg_lemma = "et".join(fg_lemma.split("⁊"))
                    # Remove any spacing at the beginning/end of the identified lemma
                    fg_lemma = fg_lemma.strip()
                    # Render the identified lemma in lower-case
                    fg_lemma = fg_lemma.lower()

                else:
                    raise RuntimeError("Unexpected number of Lemmata found for <note> tag.")

                # Isolate the target line in the base-text identified by the gloss
                # If this is a different line from the last gloss, track this information too
                try:
                    target_line_id = full_note["target"]
                except KeyError:
                    print(full_note)
                    print(type(full_note))
                    raise RuntimeError
                if target_line_id:
                    target_line_id = target_line_id[1:]
                else:
                    if verbose:
                        print(f"    Skipping gloss number {full_note["n"]}; "
                              f"no target line in the base-text found for this gloss")
                    skip_count += 1
                    # lemma_note = f"<!-- re-examine: No line with ID {target_line_id} occurs in the base-text -->"
                    continue

                if target_line_id == cur_line:
                    new_line = False
                else:
                    cur_line = target_line_id
                    new_line = True

                identified_glosses.append(
                    [original_note, span[0], span[1], fg_lemma, target_line_id, new_line, lemma_note]
                )  # Add Lemma Note if continuing to use them

            # Keep track of how many glosses have been found which can be assigned to a lemma
            no_glosses = len(identified_glosses)
            percent_divisor = 100 / no_glosses
            percent_left = 100

            if verbose:
                print(f"  Found {len(identified_glosses)} usable glosses\n    {skip_count} glosses skipped\n\n"
                      f"    Assigning glosses to lemmata:")

            # Assign glosses to lemmata in the base-text, and generate notes regarding lemmata where necessary
            for gloss_no, identified_gloss in enumerate(identified_glosses):

                # Calculate and output the number of items remaining while process runs
                if verbose:
                    percent_complete = gloss_no * percent_divisor
                    exact_left = 100 - percent_complete
                    if exact_left == 100:
                        print(f"    {exact_left}% remaining.")
                    elif exact_left <= percent_left - 0.1:
                        percent_left = round(exact_left, 1)
                        print(f"    {percent_left}% remaining.")

                # Name the elements of the identified gloss
                found_gloss, fg_lemma = identified_gloss[0], identified_gloss[3]
                target_line_id, new_line, lemma_note = identified_gloss[4], identified_gloss[5], identified_gloss[6]
                lemma_key = "No lemma found"

                # Using the target from the gloss edition, find the words from the correct line in the base-text
                lemma_list = base_lines.get(target_line_id)

                # Find tokenised words in the segment and pair them with their unique ID numbers
                lemma_lookup = [(lemma.get_text(), lemma["xml:id"]) for lemma in lemma_list]

                # Replace abbreviations with full forms in the base-text segment
                lemma_lookup = [i if i[0] != "⁊" else ("et", i[1]) for i in lemma_lookup]

                # Put the token itself in lower-case and swap out v's for u's
                lemma_lookup = [(i[0].lower(), i[1]) for i in lemma_lookup]
                lemma_lookup = [("u".join(i[0].split("v")), i[1]) for i in lemma_lookup]
                # Remove undesirable characters from the base-text lookup list
                unds_list = [".", ",", ":", ";", "«", "»"]
                lemma_lookup = [i for i in lemma_lookup if i[0] not in unds_list]
                lemmas_only = [i[0] for i in lemma_lookup]

                # If no corresponding text segment can be found in the base-text line
                if target_line_id and not lemma_list:
                    # lemma_note = f"<!-- re-examine: No line with ID {target_line_id} occurs in the base-text -->"
                    raise RuntimeError("Testing")

                # If only one exact match for the stated lemma occurs in the line from the base-text
                elif lemmas_only.count(fg_lemma) == 1:
                    for lem_tok_inst in lemma_lookup:
                        lem_tok = lem_tok_inst[0]
                        if lem_tok == fg_lemma:
                            lemma_key = f"#{lem_tok_inst[1]}"
                            break

                # If no exact match for the stated lemma occurs in the segment from the base text
                elif lemmas_only.count(fg_lemma) == 0:

                    # First check to see if standardising u/v allows a match to be found
                    u_match = False
                    this_fg_lemma = "u".join(fg_lemma.split("v"))
                    for lem_tok_inst in lemma_lookup:
                        lem_tok = lem_tok_inst[0]
                        if lem_tok == this_fg_lemma:
                            u_match = True
                            lemma_key = f"#{lem_tok_inst[1]}"
                            break

                    # If no match is found still, use Levenshtein distance to find the closest match
                    if not u_match:
                        lev_list = [
                            (
                                ed(this_fg_lemma, lem[0]), norm_ld(this_fg_lemma, lem[0]), lem
                            ) for lem in lemma_lookup
                        ]
                        min_lev = min(lev_list, key=lambda t: t[0])
                        min_lev_norm = min_lev[1]
                        lemma_key = f"#{min_lev[2][1]}"
                        if not lemma_note:
                            lemma_note = (f"<!-- re-examine: Levenshtein distance used to find the closest match - "
                                          f"Edit distance: {min_lev[0]} ('{this_fg_lemma}' to '{min_lev[2][0]}') - "
                                          f"Difference: {min_lev_norm}% -->")
                        else:
                            lemma_note = lemma_note[:-1] + (f" Levenshtein distance used to find the closest match - "
                                                            f"Edit distance: "
                                                            f"{min_lev[0]} ('{this_fg_lemma}' to '{min_lev[2][0]}') - "
                                                            f"Difference: {min_lev_norm}% -->")

                # If more than one match for the stated lemma occurs in the segment from the base text
                # Match the token to all possible lemmata
                elif lemmas_only.count(fg_lemma) > 1:
                    for lem_tok_inst in lemma_lookup:
                        lem_tok = lem_tok_inst[0]
                        lem_id = lem_tok_inst[1]
                        # If this is the first lemma matched in the base-text segment
                        if lem_tok == fg_lemma and lemma_key == "No lemma found":
                            lemma_key = f"#{lem_id}"
                        # If this is not the first lemma matched in the base-text segment
                        elif lem_tok == fg_lemma and lemma_key[0] == "#":
                            additional_lemma_key = lem_id
                            lemma_key = f"""{lemma_key} #{additional_lemma_key}"""
                        if not lemma_note:
                            lemma_note = "<!-- re-examine: Multiple possible matches found -->"
                        else:
                            lemma_note = lemma_note[:-1] + " Multiple possible matches found -->"

                if lemma_key == "No lemma found":
                    raise RuntimeError(f"No lemma found for gloss: {target_line_id}")

                # Add the lemma found lemma key, and any lemma notes to the identified gloss list
                identified_gloss[6] = lemma_note
                identified_gloss.append(lemma_key)

                # Generate the updated gloss, and add it to the identified gloss list
                updated_gloss = found_gloss[:]
                updated_gloss = f'''target="{lemma_key}"'''.join(
                    updated_gloss.split(f'''target="#{target_line_id}"''')
                )
                if lemma_note:
                    if found_gloss.rfind("\n") + len("\n") < found_gloss.rfind("</note>"):
                        note_indent = found_gloss[found_gloss.rfind("\n") + len("\n"): found_gloss.rfind("</note>")]
                    else:
                        note_indent = ""
                    updated_gloss = f"{lemma_note}\n{note_indent}{updated_gloss}"
                identified_gloss.append(updated_gloss)

                # Add the identified gloss list to the list of all identified glosses
                identified_glosses[gloss_no] = identified_gloss

            if verbose:
                print(f"    0.0% remaining.\n\nSaving gloss annotations\n")

            # Assign glosses to lemmata in the base-text, and generate notes regarding lemmata where necessary
            for gloss_no, identified_gloss in enumerate(reversed(identified_glosses)):

                # Name the elements of the identified gloss
                found_gloss, find_pos, end_pos = identified_gloss[0], identified_gloss[1], identified_gloss[2]
                lemma_note, lemma_key, updated_gloss = identified_gloss[6], identified_gloss[7], identified_gloss[8]

                if output_text[find_pos:end_pos] == found_gloss:
                    output_text = output_text[:find_pos] + updated_gloss + output_text[end_pos:]
                else:
                    raise RuntimeError(f"Expected gloss:\n\n{found_gloss}\n\nto occur at index {find_pos}, "
                                       f"instead found\n\n{output_text[find_pos:end_pos]}")

            # Create a new file path with "_lemmatised" appended to the filename
            directory, filename = os.path.split(gloss_file)
            new_filename = os.path.splitext(filename)[0] + '_lemmatised.xml'  # Need a better solution (!!!)
            new_file_path = os.path.join(directory, new_filename)

            # Save the modified content to the new file path
            with open(new_file_path, 'w', encoding="utf-8") as new_file:
                new_file.write(output_text)


if __name__ == "__main__":

    find_lems()
