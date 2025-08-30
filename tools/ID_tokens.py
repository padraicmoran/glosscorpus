import os
import re
from datetime import date
import string
from bs4 import BeautifulSoup
from nltk import wordpunct_tokenize as tze


def id_tokens(verbose=False):
    """
    Searches for all base-text files and tokenises their contents, tagging and numbering each word.

    Creates an annotated copy of the file within the same directory as the original files.
    """

    # Search for, and collect .xml files for base-texts
    xml_files = {}
    tools_dir = os.getcwd()
    tools_parent = os.path.dirname(tools_dir)
    texts_dir = os.path.join(tools_parent, "data", "texts")
    if os.path.isdir(texts_dir):
        base_texts = os.listdir(texts_dir)
        for base_txt in base_texts:
            txt_dir = os.path.join(texts_dir, base_txt)
            base_txt_path = os.path.join(txt_dir, "basetext.xml")
            if os.path.isfile(base_txt_path):
                xml_files[base_txt] = base_txt_path
            elif verbose:
                print(f"Skipping base text, {base_txt}, could not find file path:\n    {base_txt_path}")
    elif verbose:
        print(f"Token annotation abandoned; cannot find texts directory at specified location:\n    {texts_dir}")

    # For each .xml file found in the base-texts directory
    for old_xml in xml_files:

        if verbose:
            print(f"Currently working on base-text file for {old_xml}\n  Tokenising file's contents")

        # Open the TEI file as raw text, and parse the TEI file with BeautifulSoup
        with open(xml_files.get(old_xml), "r", encoding="utf-8") as xml_file:
            content = xml_file.read()
        base_soup = BeautifulSoup(content, "xml")
        output_text = content[:]

        # Check if the base-text file has already been tokenised
        file_desc = base_soup.find("fileDesc")
        if file_desc:
            revision_desc = base_soup.find("teiHeader").find("revisionDesc")
            # If no revision history exists for the file, assume it hasn't been tokenised, and create a revision history
            if not revision_desc:
                if verbose:
                    print(f"    No <revisionDesc> found in {old_xml} base-text file, creating one...")
                # Insert the new revision history after the file description in the base-text file
                indent_match = re.search(r"^(\s*)</fileDesc>", output_text, re.MULTILINE)
                if indent_match:
                    indent = indent_match.group(1)
                else:
                    indent = ""
                revision_desc = base_soup.new_tag("revisionDesc")
                today = date.today().strftime("%Y-%m-%d")
                change = base_soup.new_tag("change")
                change.attrs["when"] = today
                change.attrs["what"] = "revision_history_created"
                change.attrs["who"] = "#tokeniser"
                change.attrs["how"] = "automatic"
                change.string = ("Automatically processed: "
                                 "The revisionDesc section was automatically created during token annotation.")
                revision_desc.append(change)
                new_revision_str = str(revision_desc.prettify())
                new_str_with_tabs = re.sub(
                    r'^( +)', lambda m: '\t' * (len(m.group(1)) // 1), new_revision_str, flags=re.MULTILINE
                )
                new_revision = new_str_with_tabs.strip()
                new_revision = f"\n{indent}" + f"\n{indent}".join(new_revision.split("\n"))
                closing_index = output_text.find("</fileDesc>")

                # Add the new revision history to the base-text file,
                # but don't save it yet, as the token annotation process may fail
                output_text = (
                        output_text[:closing_index + len("</fileDesc>")]
                        + new_revision
                        + output_text[closing_index + len("</fileDesc>"):]
                )
                if verbose:
                    print("      <revisionDesc> section added after <fileDesc> section in file.")

                # Reopen and parse the base-text file, which now includes revision history section
                base_soup = BeautifulSoup(output_text, "xml")
                revision_desc = base_soup.find("teiHeader").find("revisionDesc")

            # If a revision history already existed for the file, or if one has just been created,
            # add new revision information to it to inform about token annotation
            indent_match = re.search(r"^(\s*)</revisionDesc>", output_text, re.MULTILINE)
            if indent_match:
                indent = indent_match.group(1)
            else:
                indent = ""
            today = date.today().strftime("%Y-%m-%d")
            change = base_soup.new_tag("change")
            change.attrs["when"] = today
            change.attrs["what"] = "tokenisation_and_annotation"
            change.attrs["who"] = "#tokeniser"
            change.attrs["how"] = "automatic"
            change.string = ("Automatically processed: "
                             "Words and punctuation were separated into tokens. "
                             "Each token was tagged with either w (word) or pc (punctuation), "
                             "and given a unique ID number based on its line ID and its position in that line.")
            new_change_str = str(change.prettify())
            new_str_with_tabs = re.sub(
                r'^( +)', lambda m: '\t' * (len(m.group(1)) // 1), new_change_str, flags=re.MULTILINE
            )
            new_revision = new_str_with_tabs.strip()
            new_revision = "\t" + f"\n{indent}\t".join(new_revision.split("\n")) + f"\n{indent}"
            closing_index = output_text.find("</revisionDesc>")

            # Add the revision history update to the base-text file,
            # but don't save it yet, as the token annotation process may fail
            output_text = (
                    output_text[:closing_index]
                    + new_revision
                    + output_text[closing_index:]
            )
            if verbose:
                print("    New <change> added to <revisionDesc> section in file to detail token annotation")

            # Reopen and parse the base-text file, which now includes the revision history update
            base_soup = BeautifulSoup(output_text, "xml")
            revision_desc = base_soup.find("teiHeader").find("revisionDesc")

        # If the base-text file has no file description, it is faulty, and the revision history can't be inserted
        # Abandon attempt to work with this base-text
        else:
            if verbose:
                print(f"    No <fileDesc> found for {old_xml} base-text, cannot insert <revisionDesc>")
                print(f"Token annotation abandoned for {old_xml} base-text\n")
            continue

        # Add a responsibility statement for the tokeniser if one does not already exist
        title_stmt = base_soup.find("teiHeader").find("fileDesc").find("titleStmt")
        if title_stmt:
            resp_stmts = title_stmt.find_all("respStmt")
            # Insert a new responsibility statement at the bottom of the title statement in the base-text file
            # if one does not already occur there
            if not resp_stmts:
                if verbose:
                    print(f"    No responsibility statement found in {old_xml} base-text file, creating one...")
                indent_match = re.search(r"^(\s*)</titleStmt>", output_text, re.MULTILINE)
                if indent_match:
                    indent = indent_match.group(1)
                else:
                    indent = ""
                resp_stmt = base_soup.new_tag("respStmt")
                resp_stmt.attrs["xml:id"] = "tokeniser"
                responsibility = base_soup.new_tag("resp")
                responsibility.string = (
                    "Automatic separation of word and punctuation tokens for base-texts, "
                    "and assignment of unique ID numbers to each token"
                )
                resp_name = base_soup.new_tag("name")
                resp_name.string = (
                    "ID_tokens.py"
                )
                resp_stmt.append(responsibility)
                resp_stmt.append(resp_name)

                new_resp_str = str(resp_stmt.prettify())
                new_resp_with_tabs = re.sub(
                    r'^( +)', lambda m: '\t' * (len(m.group(1)) // 1), new_resp_str, flags=re.MULTILINE
                )
                new_resp_revision = new_resp_with_tabs.strip()
                new_resp_revision = "\t" + f"\n{indent}\t".join(new_resp_revision.split("\n")) + f"\n{indent}"
                closing_index = output_text.find("</titleStmt>")

                # Add the new responsibility statement to the base-text file,
                # but don't save it yet, as the token annotation process may fail
                output_text = (
                        output_text[:closing_index]
                        + new_resp_revision
                        + output_text[closing_index:]
                )
                if verbose:
                    print("      Responsibility statement added to base-text file for tokeniser")

                # Reopen and parse the base-text file, which now includes a responsibility statement
                base_soup = BeautifulSoup(output_text, "xml")

            # If one or more responsibility statements exist, but they were not made by the tokensier
            # Insert a new responsibility statement after the last one in the base-text file
            else:
                # Check that none of the responsibility statements relate to the tokeniser
                tokenised = False
                for resp_stmt in resp_stmts:
                    if resp_stmt.get("xml:id") == "tokeniser":
                        tokenised = True
                        break

                # If none of the responsibility statements relate to the tokeniser
                if not tokenised:
                    # Insert tokenisation and token annotation responsibility information into the base-text file
                    indent_match = re.search(r"^(\s*)</titleStmt>", output_text, re.MULTILINE)
                    if indent_match:
                        indent = indent_match.group(1)
                    else:
                        indent = ""
                    resp_stmt = base_soup.new_tag("respStmt")
                    resp_stmt.attrs["xml:id"] = "tokeniser"
                    responsibility = base_soup.new_tag("resp")
                    responsibility.string = (
                        "Automatic separation of word and punctuation tokens for base-texts, "
                        "and assignment of unique ID numbers to each token"
                    )
                    resp_name = base_soup.new_tag("name")
                    resp_name.string = (
                        "ID_tokens.py"
                    )
                    resp_stmt.append(responsibility)
                    resp_stmt.append(resp_name)

                    new_resp_str = str(resp_stmt.prettify())
                    new_resp_with_tabs = re.sub(
                        r'^( +)', lambda m: '\t' * (len(m.group(1)) // 1), new_resp_str, flags=re.MULTILINE
                    )
                    new_resp_revision = new_resp_with_tabs.strip()
                    new_resp_revision = f"\n{indent}\t" + f"\n{indent}\t".join(new_resp_revision.split("\n"))
                    closing_index = output_text.find("</respStmt>")

                    # Add the revision history update to the base-text file,
                    # but don't save it yet, as the token annotation process may fail
                    output_text = (
                            output_text[:closing_index + len("</respStmt>")]
                            + new_resp_revision
                            + output_text[closing_index + len("</respStmt>"):]
                    )
                    if verbose:
                        print("      Responsibility statement added to base-text file for tokeniser")

        # If the base-text file has no title statement, it is faulty, and the responsibility statement can't be inserted
        # Abandon attempt to work with this base-text
        else:
            if verbose:
                print(f"    No <titleStmt> found for {old_xml} base-text, cannot insert <respStmt>")
                print(f"Token annotation abandoned for {old_xml} base-text\n")
            continue

        # Look through all listed changes in the file's revision history
        changes = revision_desc.find_all("change")
        already_tokenised = False
        for change in changes:
            tok_check = change["what"]
            # Check if a listed change suggests that the file either:
            # 1. Predates automatic token annotation, but nevertheless should not be tokenised
            # 2. Has already been tokenised and annotated
            if tok_check in ["pre_tokenisation", "token_annotation"]:
                already_tokenised = True
                break
        # If a base-text has already been tokenised, or predates tokenisation, skip the file with explanatory print out
        if already_tokenised:
            if verbose:
                print(f"Skipping token annotation for {old_xml} and reverting all changes, "
                      f"file has already been tokenised and/or annotated\n")
            continue

        # If the text hasn't been tokenised yet, get the contents of each line of text
        line_tags = base_soup.find_all("ab")

        # Process each line of text in the TEI file
        all_lines = list()
        full_count = len(line_tags)
        skip_count = 0
        for base_line in line_tags:

            # Use BS to find unique IDs for each collected line
            # To save processing power, this is done before any text is processed, in case a line can be skipped
            line_id = base_line.get("xml:id")
            if not line_id:
                if verbose:
                    print(f"    Skipping line as its ID could not be determined:\n      {base_line}")
                skip_count += 1
                continue

            # Next use unique IDs with regex to find the indices of the line in the base-file's raw text
            # (DOTALL matches across multiple lines as TEI lines can wrap)
            line_pattern = re.compile(
                rf'(<ab[^>]*xml:id="{re.escape(line_id)}"[^>]*>.*?</ab>)', re.DOTALL
            )
            match = line_pattern.search(output_text)
            if not match:
                if verbose:
                    print(f"    Skipping line {line_id} as no line with this ID could be found in the text file:"
                          f"\n      {base_line}")
                skip_count += 1
                continue

            # Get the start and end indices of the match found in the original text
            span = [i for i in match.span()]
            original_ab = match.group(1)
            ab_count = original_ab.count("<ab ")
            end_ab_count = 1

            # In case any nested <ab> tags occur, and the regex may not have found the correct closing </ab> tag,
            # iterate over the original text until the correct closing tag is found by counting the number of
            # opening and closing tags within each match, and updating the match indices until the number is even
            if ab_count > end_ab_count:
                while ab_count > end_ab_count:
                    span = [span[0], output_text.find("</ab>", span[1]) + len("</ab>")]
                    original_ab = output_text[span[0]:span[1]]
                    ab_count = original_ab.count("<ab ")
                    end_ab_count = original_ab.count("</ab>")
            elif ab_count != end_ab_count:
                if verbose:
                    print(f"    Skipping line {line_id}, as the correct closing </ab> tag could not be found.\n"
                          f"{original_ab}\n")
                skip_count += 1
                continue

            # Get the raw text of the line enclosed by <ab> tags
            match_raw = re.search(r"<ab[^>]*>(.*)</ab>", original_ab, re.DOTALL)
            if match_raw:
                raw_text = match_raw.group(1)
            else:
                if verbose:
                    print(
                        f"    Skipping line {line_id} as the raw text between <ab> tags could not be extracted.\n"
                    )
                skip_count += 1
                continue

            # Tokenise the text
            line_text = base_line.get_text()
            clean_tokens = tze(line_text)

            # Account for any TEI tags that may occur using regex
            tokens_with_tags = list()
            i = 0
            n = len(raw_text)
            for tok in clean_tokens:
                # 1) Skip whitespace between tokens in the tagged string
                while i < n and raw_text[i].isspace():
                    i += 1

                snippet = ""

                # 2) Include any opening tags that come right before the token’s first visible char
                #    (we do NOT consume closing or self-closing tags here; those belong to the
                #     previous token or to the next token if they introduce visible text).
                while i < n and raw_text[i] == '<':
                    j = raw_text.find('>', i)
                    if j == -1:
                        raise ValueError("Malformed tag (missing '>').")
                    tag = raw_text[i:j + 1]
                    if tag.startswith('</') or tag.endswith('/>'):
                        # leave these for step (4) of the previous or next token
                        break
                    snippet += tag
                    i = j + 1

                # 3) Consume text/tags until we have matched the entire token (char-by-char).
                k = 0  # index in token
                while k < len(tok):
                    if i >= n:
                        raise ValueError(f"Ran out of text while matching token {tok!r}.")
                    ch = raw_text[i]
                    if ch == '<':
                        j = raw_text.find('>', i)
                        if j == -1:
                            raise ValueError("Malformed tag (missing '>').")
                        snippet += raw_text[i:j + 1]
                        i = j + 1
                        continue
                    snippet += ch
                    if ch.isspace():
                        # ignore stray whitespace inside markup regions
                        i += 1
                        continue
                    if ch != tok[k]:
                        raise ValueError(
                            f"Mismatch while matching token {tok!r}: expected {tok[k]!r}, saw {ch!r} at pos {i}."
                        )
                    k += 1
                    i += 1

                # 4) After finishing the token’s visible chars, greedily include any immediately
                #    adjacent end tags or empty elements (e.g. </term>, </hi>, <lb/>) that close
                #    markup for this token, but stop before an opening tag that would start the next token.
                while i < n and raw_text[i] == '<':
                    j = raw_text.find('>', i)
                    if j == -1:
                        raise ValueError("Malformed tag (missing '>').")
                    tag = raw_text[i:j + 1]
                    if tag.startswith('</') or tag.endswith('/>'):
                        snippet += tag
                        i = j + 1
                    else:
                        break

                tokens_with_tags.append(snippet)

            all_lines.append([clean_tokens, tokens_with_tags, original_ab, raw_text, line_id, span[0], span[1]])

        if verbose:
            print(f"  Tokenisation complete for base-text file: {old_xml}"
                  f"\n  Adding unique IDs to each token in file: {old_xml}")

        # Measure the amount of time taken to apply unique IDs to tokens
        original_len = len(all_lines)
        percent_divisor = 100 / original_len
        percent_left = 100
        long_loop = 500000

        for line_num, line in enumerate(all_lines):

            clean_tokens, tei_tokens, raw_line, raw_text = line[0], line[1], line[2], line[3]
            line_id, start, end = line[4], line[5], line[6]

            # Make sure that the tokenised text can be located within the raw text for the line
            updated_line = raw_line[:]
            if raw_text not in updated_line:
                if verbose:
                    print(f"  Skipping token annotation for line {line_id} and reverting all changes, "
                          f"cannot match raw text with line from original TEI document\n")
                skip_count += 1
                continue
            ab_tags = updated_line.split(raw_text)

            if updated_line not in output_text:
                if verbose:
                    print(f"  Skipping token annotation for line {line_id} and reverting all changes, "
                          f"cannot find line in original TEI document\n")
                skip_count += 1
                continue

            tag_indent = output_text[(output_text[:start]).rfind("\n") + len("\n"):start]
            word_indent = tag_indent + "\t"

            annotated_tokens = [(f"{line_id}__{toknum + 1}", tok) for toknum, tok in enumerate(tei_tokens)]
            annotated_tokens = [
                f'\n{word_indent}<pc xml:id="{tagged_tok[0]}">{tagged_tok[1]}</pc>' if all(
                    char in string.punctuation + "«»" for char in tagged_tok[0]
                ) else f'\n{word_indent}<w xml:id="{tagged_tok[0]}">{tagged_tok[1]}</w>'
                for tagged_tok in annotated_tokens
            ]

            annotated_line = "".join([ab_tags[0]] + annotated_tokens + ["\n", tag_indent, ab_tags[1]])

            line.append(annotated_line)
            all_lines[line_num] = line

            if original_len >= long_loop and verbose:
                percent_complete = line_num * percent_divisor
                exact_left = 100 - percent_complete
                if exact_left == 100:
                    print(f"    {exact_left}% remaining.")
                elif exact_left <= percent_left - 0.1:
                    percent_left = round(exact_left, 2)
                    print(f"    {percent_left}% remaining.")

        # Insert annotated tokens into the correct positions in the raw TEI file's text
        for line in reversed(all_lines):

            raw_line, start, end, annotated_line, line_id = line[2], line[5], line[6], line[7], line[4]

            if output_text[start:end] == raw_line:
                output_text = output_text[:start] + annotated_line + output_text[end:]
            else:
                if verbose:
                    print(f"  Skipping token annotation for line {line_id} and reverting all changes, "
                          f"cannot find line in original TEI document\n")
                skip_count += 1
                continue

        # Save the modified content to the new file path
        with open(xml_files.get(old_xml), 'w', encoding="utf-8") as new_file:
            new_file.write(output_text)

        if verbose:
            print(f"Token annotation complete for file:\n    {xml_files.get(old_xml)}\n"
                  f"  Lines annotated: {full_count-skip_count}\n  Lines skipped: {skip_count}\n")


if __name__ == "__main__":

    id_tokens(True)
