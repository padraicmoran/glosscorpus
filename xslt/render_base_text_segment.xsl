<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [
<!ENTITY nbsp "&#160;">
]>
<xsl:stylesheet version="1.0" 
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  >

  <xsl:output 
    method="html" 
    encoding="UTF-8" />
  
  <xsl:preserve-space elements=""/>
    
  <xsl:template match="/">
    <xsl:apply-templates/>
  </xsl:template>

  <!-- lemmata -->
  <xsl:template match="w|seg"><xsl:apply-templates/></xsl:template>

  <!-- manuscript transcription -->
  <xsl:template match="add"><sup><ins href="javascript:void(0);" class="text-reset text-decoration-none" data-bs-toggle="tooltip" title="Secondary addition in MS"><xsl:apply-templates/></ins></sup></xsl:template>
  <xsl:template match="corr"><span class="text-secondary small">(= <a href="javascript:void(0);" class="text-reset text-decoration-none" title="Editorial correction" data-bs-toggle="tooltip"><xsl:apply-templates/></a>)</span></xsl:template>
  <xsl:template match="del"><del href="javascript:void(0);" class="text-reset text-decoration-line-through"  title="Deleted text" data-bs-toggle="tooltip"><xsl:apply-templates/></del></xsl:template>
  <xsl:template match="ex"><a href="javascript:void(0);" class="text-reset text-decoration-none" title="Editorial expansion" data-bs-toggle="tooltip"><i><xsl:apply-templates/></i></a></xsl:template>
  <xsl:template match="expan"><a href="javascript:void(0);" class="text-reset text-decoration-none" title="Editorial expansion" data-bs-toggle="tooltip"><i><xsl:apply-templates/></i></a></xsl:template>
  <xsl:template match="g"><span class="border p-1 small text-secondary"><xsl:apply-templates/></span></xsl:template>  
  <xsl:template match="gap"><span class="text-secondary">[<a href="javascript:void(0);" class="text-reset text-decoration-none" data-bs-toggle="tooltip"><xsl:attribute name="title">Gap in transcription: <xsl:value-of select="@extent"/> <xsl:value-of select="@reason"/></xsl:attribute>...</a>]</span></xsl:template>
  <xsl:template match="lb"><span class="text-secondary small">|</span></xsl:template>
  <xsl:template match="note"><a href="javascript:void(0);" class="text-secondary small" data-bs-toggle="tooltip"><xsl:attribute name="title"><xsl:apply-templates/></xsl:attribute>[NOTE]</a></xsl:template>
  <xsl:template match="sic"><xsl:apply-templates/><span class="text-secondary small"> (<i>sic</i>)</span></xsl:template>
  <xsl:template match="space"><span class="text-secondary">[<a href="javascript:void(0);" class="text-reset text-decoration-none" data-bs-toggle="tooltip"><xsl:attribute name="title">Space left in MS: <xsl:value-of select="@extent"/></xsl:attribute>_</a>]</span> </xsl:template>
  <xsl:template match="supplied"><span class="text-secondary">&lt;<a href="javascript:void(0);" class="text-reset text-decoration-none" title="Text supplied by editor" data-bs-toggle="tooltip"><xsl:apply-templates/></a>&gt;</span></xsl:template>
  <xsl:template match="surplus"><span class="text-secondary small">(</span><a href="javascript:void(0);" class="text-reset text-decoration-none" title="Text marked as redundant by editor" data-bs-toggle="tooltip"><xsl:apply-templates/></a><span class="text-secondary">)</span></xsl:template>
  <xsl:template match="unclear"><span class="text-secondary small">[</span><a href="javascript:void(0);" class="text-reset text-decoration-none" title="Reading unclear" data-bs-toggle="tooltip"><xsl:apply-templates/><span class="text-secondary"><sup>?</sup></span></a><span class="text-secondary small">]</span></xsl:template>
</xsl:stylesheet>